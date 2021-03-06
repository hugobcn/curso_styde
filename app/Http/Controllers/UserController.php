<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Forms\UserForm;
use App\Http\Requests\{CreateUserRequest, UpdateUserRequest};
use App\{Profession, Skill, User};
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        //$users = User::orderBy('created_at', 'DESC')->simplePaginate(15);
        $users = User::query()
            ->when(request('team'), function ($query, $team){
                if( $team === 'with_team'){
                    $query->has('team');
                } elseif ($team === 'without_team'){
                    $query->doesntHave('team');
                }
            })
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });

            })
            ->orderByDesc('created_at')
//            ->toSql();
            ->paginate(15);

//        dd($users);

        $title = 'Listado de usuarios';

        return view('users.index', compact('title', 'users'));
    }

    public function show(User $user)
    {
        //$user = User::findOrFail($id);
        /*if($user==null){
            return \response()->view('errors.404', [], 404);
        }*/
        return view('users.show', compact('user'));
    }

    public function create()
    {
        //$user = new User;

        return new UserForm('users.create', new User);
//        return view('users.create', compact('user'))
//            ->with($this->formsData());
    }

    public function store(CreateUserRequest $request)
    {
        $request->createUser();
        return redirect()->route('users.index');
    }

    public function edit(User $user){
        return view('users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $request->updateUser($user);

        return redirect()->route('users.show', ['user' => $user]);
    }

    public function destroy($id)
    {
        $user = User::onlyTrashed()->where('id', $id)->firstOrFail();

        //Delete user profile
        $user->profile()->forceDelete();

        //Delete relationship whith pivot table
        $user->skills()->detach();
        //$user->skills()->sync([]);

        //Delete user
        $user->forceDelete();

        return redirect()->route('trashed.index');
    }

    public function trash(User $user)
    {
        $user->profile()->delete();

        DB::table('user_skill')
            ->where('user_id', $user->id)
            ->update(array('deleted_at' => DB::raw('NOW()')));

        $user->delete();

        return redirect()->route('users.index');
    }

    public function restore($id)
    {
        $user = User::onlyTrashed()->where('id', $id)->firstOrFail();
        $user->profile()->restore();

        DB::table('user_skill')
            ->where('user_id', $user->id)
            ->update(array('deleted_at' => null));

        $user->restore();

        return redirect()->route('users.index');
    }

    public function destroyOldTrashedUsers()
    {
        $users = User::onlyTrashed()->where('deleted_at', '<=', DB::raw('(NOW() - INTERVAL 2 WEEK)'))->get();

        $count = 0;
        foreach ($users as $user){
            $user->profile()->forceDelete();
            $user->skills()->detach();
            $user->forceDelete();

            $count++;
        }

        return response("We have deleted {$count} users from the trash", 200)
            ->header('Content-Type', 'text/html');
    }
}
