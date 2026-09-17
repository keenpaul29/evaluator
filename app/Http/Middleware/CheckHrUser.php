<?php

namespace App\Http\Middleware;

use App\Models\HrUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckHrUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $hrUserId = $request->input('hr_user_id');

        if ($hrUserId) {
            $hrUser = HrUser::find($hrUserId);

            if (! $hrUser) {
                return response()->json(['error' => 'Invalid HR user ID'], 401);
            }

            $request->merge(['hr_user' => $hrUser]);
        }

        return $next($request);
    }
}
