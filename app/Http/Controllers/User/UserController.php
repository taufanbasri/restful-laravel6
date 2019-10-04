<?php

namespace App\Http\Controllers\User;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Events\Registered;
use App\Http\Controllers\ApiController;
use App\Transformers\UserTransformer;
use Illuminate\Auth\Access\AuthorizationException;

class UserController extends ApiController
{
    public function __construct() {
        $this->middleware('auth:api')->except('store', 'verify', 'resend');
        $this->middleware('client.credentials')->only('store', 'resend');
        $this->middleware(['verify' => true])->only('store', 'update');
        $this->middleware('transform.input:' . UserTransformer::class)->only('store', 'update');
        $this->middleware('scope:manage-account')->only('show', 'update');
        $this->middleware('can:view,user')->only('show');
        $this->middleware('can:update,user')->only('update');
        $this->middleware('can:delete,user')->only('destroy');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->allowedAdminAction();

        $users = User::all();

        return $this->showAll($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        $data['password'] = bcrypt($request->password);
        $data['verified'] = User::UNVERIFIED_USER;
        $data['admin'] = User::REGULAR_USER;

        $user = User::create($data);

        event(new Registered($user));

        return $this->showOne($user, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return $this->showOne($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'email' => 'email|unique:users,email,' . $user->id,
            'password' => 'min:8|confirmed',
            'verified' => 'in:' . User::VERIFIED_USER . ',' . User::UNVERIFIED_USER,
            'admin' => 'in:' . User::ADMIN_USER . ',' . User::REGULAR_USER,
        ]);

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email') && (strtolower($user->email) != $data['email'])) {
            $user->email = $data['email'];
            $user->email_verified_at = null;
            $user->verified = 'false';
        }

        if ($request->has('password')) {
            $user->password = bcrypt($data['password']);
        }

        if ($request->has('admin')) {
            $this->allowedAdminAction();

            if (!auth()->user()->hasVerifiedEmail()) {
                return $this->errorResponse('Only verified users can modify the admin field', 409);
            }

            $user->admin = $data['admin'];
        }

        if ($request->has('verified')) {
            $this->allowedAdminAction();
            
            if (!auth()->user()->isAdmin()) {
                return $this->errorResponse('Only admin users can modify the verified field', 409);
            }

            $user->email_verified_at = now();
            $user->verified = $data['verified'];
        }

        if ($user->isClean()) {
            return $this->errorResponse('You need to specify a different value to update', 422);
        }

        $user->save();

        return $this->showOne($user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();

        return $this->showOne($user);
    }

    /**
     * Information about signed user
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return $this->showOne($user);
    }

    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function verify(Request $request, User $user, $hash)
    {
        // if (! hash_equals((string) $request->route('user'), (string) $request->user()->getKey())) {
        //     throw new AuthorizationException();
        // }

        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            throw new AuthorizationException;
        }

        if ($user->hasVerifiedEmail()) {
            return $this->showMessage('Account already verified');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            
            $user->verified = 'true';
            $user->save();
        }

        return $this->showMessage('The account has been verified successfully');
    }

    /**
     * Resend the email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function resend(User $user)
    {
        if ($user->hasVerifiedEmail()) {
            return $this->showMessage('Account already verified');
        }

        $user->sendEmailVerificationNotification();

        return $this->showMessage('A fresh verification link has been sent to your email address.');
    }
}
