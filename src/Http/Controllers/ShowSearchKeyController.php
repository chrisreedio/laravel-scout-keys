<?php

namespace ChrisReedIO\ScoutKeys\Http\Controllers;

use ChrisReedIO\ScoutKeys\Contracts\SearchUser;
use ChrisReedIO\ScoutKeys\Models\SearchKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

use function abort;
use function auth;
use function is_null;
use function response;

class ShowSearchKeyController extends Controller
{
    public function __invoke()
    {
        // Get a search key, if not logged in return a 401
        // If the search key is expired, request a new one
        // If the search key is not expired, return it
        if (! auth()->check()) {
            abort(401);
        }

        /** @var Model|SearchUser $user */
        $user = auth()->user();

        // Check to see if the user implements the SearchUser interface
        if (! $user instanceof SearchUser) {
            // Log::error('User ' . $user->getKey() . ' does not implement the SearchUser interface.');

            return response()->json([
                'message' => 'User does not implement the SearchUser interface',
            ], 500);
        }

        // TODO: Perform Authorization check here
        // if (!$user->can('appointments.dashboard')) {
        //     abort(403);
        // }
        // dd($user->search_key->toArray());

        if (is_null($user->searchKey)) {
            dd('here');
            $key = $user->generateSearchKey();
            $user->refresh();
        }

        if (is_null($user->search_key->key)) {
            dd('there');
            if (is_null($user->search_key->request())) {
                Log::error('User '.$user->id.' tried to request a search key but failed.');

                return response()->json([
                    'message' => 'Failed to acquire a search key',
                ], 500);
            }
        }

        return response()->json([
            'searchKey' => $user->search_key->scoped_key,
            // 'searchKey' => $user->search_key->tenant_token,
        ]);

    }
}
