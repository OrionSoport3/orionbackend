<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthenticateAndRefreshToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
            try {
                
                $user = JWTAuth::parseToken()->authenticate();

            } catch (TokenExpiredException $e) {
                try {

                    $refreshedToken = JWTAuth::refresh(JWTAuth::getToken());
                    $user = JWTAuth::setToken($refreshedToken)->toUser();
                    $request->headers->set('Authorization', 'Bearer ' . $refreshedToken);

                } catch (JWTException $e) {

                    return response()->json(['error' => 'Token cannot be refreshed, please login again'], 401);

                }
            } catch (JWTException $e) {

                return response()->json(['error' => 'Token is invalid'], 401);
            }
    
            return $next($request);
        } 
}
