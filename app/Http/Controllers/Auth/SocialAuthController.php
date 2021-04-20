<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Redirect;
use Laravel\Socialite\Facades\Socialite;
use Validator;
use Exception;
use Auth;

class SocialAuthController extends Controller
{
    // Les tableaux des providers autorisés
    protected $providers = [ "facebook" ];

    # redirection vers le provider
    public function redirect (Request $request)
    {
        $provider = $request->provider;

        // On vérifie si le provider est autorisé
        if (in_array($provider, $this->providers)) {
            return Socialite::driver($provider)->redirect(); // On redirige vers le provider
        }
        return response()->json(['error' => 'service not found'], 404);
    }

    // Callback du provider
    public function callback (Request $request)
    {

        $provider = $request->provider;

        if (in_array($provider, $this->providers)) {

            // Les informations provenant du provider
            $data = Socialite::driver($request->provider)->user();

            # Social login - register

            $email = $data->getEmail(); // L'adresse email
            $name = $data->getName(); // le nom

            # 1. On récupère l'utilisateur à partir de l'adresse email
            $user = User::where("email", $email)->first();

            # 2. Si l'utilisateur existe
            if (isset($user)) {

                // Mise à jour des informations de l'utilisateur
                $user->name = $name;
                $user->save();

                # 3. Si l'utilisateur n'existe pas, on l'enregistre
            } else {

                // Enregistrement de l'utilisateur
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => bcrypt("emilie") // On attribue un mot de passe
                ]);
            }

            # 4. On connecte l'utilisateur
            auth()->login($user);

            # 5. On redirige l'utilisateur vers /home
            if (auth()->check()) {
                $token = auth()->user()->createToken('authFumbleToken')->accessToken;
                return Redirect::to(config('app.web_url'))->header('token', $token);
//                return response()->json(['token' => $token], 200);
            };

        }
        return response()->json(['error' => 'service not found'], 404);
    }

}
