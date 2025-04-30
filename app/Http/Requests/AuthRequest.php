<?php

namespace App\Http\Requests;

use App\Http\Resources\BasicUserResource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }

    // authenticate current user (request)
    public function authenticate()
    {
        if (!Auth::attempt($this->only(['email', 'password']))) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
                'password' => trans('auth.failed'),
            ]);
        }
    }

    // create session token
    public function tokenize()
    {
        $user = $this->user();

        $this->revokeToken();

        // assign token abilities
        return $user->createToken('auth', match ($user->role) {
            'admin' => ['*'],
            default => ['user.teacher']
        })->plainTextToken;
    }

    public function revokeToken()
    {
        $this->user()->currentAccessToken()?->delete();
    }

    public function sendCurrentUser()
    {
        try {
            $token = $this->tokenize();

            /** @var User $user */
            $user = Auth::user();

            return response()->json([
                'user' => new BasicUserResource($user),
                'config' => $user->getConfigs(),
            ])->header('Resource-ID', $token);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), $e->getCode() ?? 500);
        }
    }
}