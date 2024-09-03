<?php

namespace App\Repositories;
use App\Interfaces\AuthIterface;
use App\Mail\OtpCodeMail;
use App\Models\otpCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthRepository implements AuthIterface
{
    public function register(array $data)
    {
        User::create($data);

        $otp_code = [

            'email' => $data['email'],
            'code' => rand(111111, 999999)

        ];

        otpCode::where('email', $data['email'])->delete();
        otpCode::create($otp_code);
        Mail::to($data['email'])->send(new OtpCodeMail(
            $data['name'],
            $data['email'],
            $otp_code['code']
        ));


    }

    public function checkOtpCode(array $data)
    {
        $otp_code = otpCode::where('email', $data['email'])->first();

        if (!$otp_code)
            return false;


        if ($otp_code['code'] == Hash::check($data['code'], $otp_code['code'])) {

            $user = User::where('email', $data['email'])->first();
            $user->update(['is_confirmed' => true]);

            // on supprime le OTPCode
            $otp_code->delete();

            $user->token = $user->createToken($user->id)->plainTextToken;
            return $user;
        }
        return false;
    }
    public function login(array $data)
    {
        $user = User::where('email', $data['email'])->first();
        if (!$user)
            return false;

        if (!Hash::check($data['password'], $user->password)) {
            return false;
        }

        // supprime l'ancien token a la  déconnexion
        $user->tokens()->delete();
        // ON recrer un nouveau a token a la connexion
        $user->token = $user->createToken($user->id)->plainTextToken;

        return $user;




    }
}
