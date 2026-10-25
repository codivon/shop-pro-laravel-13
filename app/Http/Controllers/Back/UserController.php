<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Imports\UsersImport;
use App\Models\NotificationManage;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Exports\UsersExport;
use App\Http\Resources\Datatable\User\UserCollection;
use App\Models\FieldValue;
use App\Models\Fild;
use App\Models\Role;
use App\Rules\NotSpecialChar;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function index()
    {
        return view('back.users.index');
    }

    public function apiIndex(Request $request)
    {
        $this->authorize('users.index');

        $users = User::filter($request);

        $users = datatable($request, $users);

        return new UserCollection($users);
    }

    public function create()
    {
        $roles = Role::latest()->get();
        $filds = Fild::where('belongs_to', 'users')->orderBy('created_at', 'desc')->get();
        return view('back.users.create', compact('roles', 'filds'));
    }

    public function edit(User $user)
    {
        $roles = Role::latest()->get();
        $filds = Fild::where('belongs_to', 'users')->orderBy('created_at', 'desc')->get();
        $fieldValues = FieldValue::where('related_id', $user->id)->get();

        return view('back.users.edit', compact('user', 'roles', 'filds', 'fieldValues'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'first_name' => ['required', 'string', 'max:255', new NotSpecialChar()],
            'last_name'  => ['required', 'string', 'max:255', new NotSpecialChar()],
            'level'      => 'in:user,admin',
            'username'   => ['required', 'string', 'unique:users'],
            'email'      => ['string', 'email', 'max:255', 'unique:users', 'nullable'],
            'password'   => ['required', 'string', 'confirmed'],
            'roles'      => 'nullable|array',
            'roles.*'    => 'exists:roles,id'
        ]);
        $birth_date = null;
        if ($request->day != "date-desc" and $request->month != "date-desc" and $request->year != "date-desc") {
            $birth_date = $request->year . '/' . $request->month . '/' . $request->day;
        }

        // اعتبار سنجی فیلد های اختصاصی
        $requiredFilds = Fild::where('belongs_to', 'users')->where('required', 1)->get();
        $validationRules = [];
        $messagesValidationRules = [];
        foreach ($requiredFilds as $requiredFild) {
            $validationRules["filds.$requiredFild->id"] = 'required';
            $messagesValidationRules["filds.$requiredFild->id.required"] = "فیلد {$requiredFild->title} اجباری است.";
        }
        $request->validate($validationRules, $messagesValidationRules);

        $user = User::create([
            'first_name'  => $request->first_name,
            'last_name'   => $request->last_name,
            'username'    => $request->username,
            'mobile'       => $request->username,
            'email'       => $request->email,
            'national_code'       => $request->national_code,
            'card_number'       => $request->card_number,
            'level'       => 'user',
            'referral_code'=>Referral::generateCode(),
            'birth_date'      => $birth_date,
            'password'    => Hash::make($request->password),
            'verified_at' => $request->verified_at ? Carbon::now() : null,
        ]);

        if ($request->hasFile('image')) {
            $avatar=uploadOptimizedImage($request->image, 'users/avatars');
            $user->update([
                'image' => $avatar
            ]);
        }

        $user->roles()->attach($request->roles);

        if (isset($request->filds) and count($request->filds)) {
            saveFieldValues($request->filds, 'users', $user->id);
        }


        session()->put('toast-success', 'کاربر با موفقیت ایجاد شد.');
        return response('success');
    }

    public function update(User $user, Request $request)
    {
        $this->validate($request, [
            'first_name' => ['required', 'string', 'max:255', new NotSpecialChar()],
            'last_name'  => ['required', 'string', 'max:255', new NotSpecialChar()],
            'level'      => 'in:user,admin',
            'username'   => ['required', 'string', "unique:users,username,$user->id"],
            'email'      => ['string', 'email', 'max:255', "unique:users,email,$user->id", 'nullable'],
            'password'   => ['nullable', 'string', 'min:6', 'confirmed'],
            'roles'      => 'nullable|array',
            'roles.*'    => 'exists:roles,id'
        ]);

        $verified_at = $user->verified_at ?: Carbon::now();
        $birth_date = null;
        if ($request->day != "date-desc" and $request->month != "date-desc" and $request->year != "date-desc") {
            $birth_date = $request->year . '/' . $request->month . '/' . $request->day;
        }

        // اعتبار سنجی فیلد های اختصاصی
        $requiredFilds = Fild::where('belongs_to', 'users')->where('required', 1)->get();
        $validationRules = [];
        $messagesValidationRules = [];
        foreach ($requiredFilds as $requiredFild) {
            $validationRules["filds.$requiredFild->id"] = 'required';
            $messagesValidationRules["filds.$requiredFild->id.required"] = "فیلد {$requiredFild->title} اجباری است.";
        }
        $request->validate($validationRules, $messagesValidationRules);

        $user->update([
            'first_name'  => $request->first_name,
            'last_name'   => $request->last_name,
            'mobile'       => $request->username,
            'username'       => $request->username,
            'email'       => $request->email,
            'national_code'       => $request->national_code,
            'card_number'       => $request->card_number,
            'level'       => 'user',
            'birth_date'      => $birth_date,
            'verified_at' => $request->verified_at ? $verified_at : null,
        ]);

        if ($request->password) {
            $password = Hash::make($request->password);

            $user->update([
                'password' => $password
            ]);

            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        if ($request->hasFile('image')) {
            $avatar=uploadOptimizedImage($request->image, 'users/avatars',$user->id);
            $user->update([
                'image' => $avatar
            ]);
        }



        $user->roles()->sync($request->roles);

        if (isset($request->filds) and count($request->filds)) {
            saveFieldValues($request->filds, 'users', $user->id);
        }

        session()->put('toast-success', 'کاربر با موفقیت ویرایش شد.');
        return response('success');
    }

    public function show(User $user)
    {
        $users_notifications = DB::table('notification_manage_users')->where('user_id', $user->id)->get();
        $users_notification_ids = [];
        foreach ($users_notifications as $users_notification) {
            $users_notification_ids[] = $users_notification->notification_manage_id;
        }
        $notifications = NotificationManage::whereIn('id', $users_notification_ids)->where('private', 'user')->get();
        return view('back.users.show', compact('user', 'notifications'));
    }

    public function destroy(User $user, $multiple = false)
    {
        if ($user->image) {
            Storage::disk('public')->delete($user->image);
        }

        $user->delete();

        if (!$multiple) {
            session()->put('toast-success', 'کاربر با موفقیت حذف شد.');
        }

        return response('success');
    }

    public function multipleDestroy(Request $request)
    {
        $this->authorize('users.delete');

        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => [
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('id', '!=', auth('adminPanel')->user()->id)->where('level', '!=', 'creator');
                })
            ]
        ]);

        foreach ($request->ids as $id) {
            $user = User::find($id);
            $this->destroy($user, true);
        }

        return response('success');
    }

    public function export(Request $request)
    {
        $this->authorize('users.export');

        $users = User::where('level', '!=', 'creator')
            ->filter($request)
            ->get();

        switch ($request->export_type) {
            case 'excel': {
                    return $this->exportExcel($users, $request);
                    break;
                }
            default: {
                    return $this->exportPrint($users, $request);
                }
        }
    }

    public function views(User $user)
    {
        $views = $user->views()->latest()->paginate(20);

        return view('back.users.views', compact('views', 'user'));
    }

    public function showProfile()
    {
        return view('back.users.profile');
    }

    public function updateProfile(Request $request)
    {
        $user = auth('adminPanel')->user();

        $this->validate($request, [
            'first_name' => 'required|string|max:191',
            'last_name' => 'required|string|max:191',
            'username' => 'required|string|max:191',
        ]);

        if ($request->password || $request->password_confirmation) {
            $this->validate($request, [
                'password' => 'required|min:6|confirmed',
                'password_confirmation' => 'required',
            ]);

            $user->password = Hash::make($request->password);
        }

        if ($request->hasFile('image')) {
            $this->validate($request, [
                'image' => 'image|max:2048',
            ]);

            $imageName = time() . '_' . $user->id . '.' . $request->image->getClientOriginalExtension();
            $request->image->move(public_path('uploads/users/'), $imageName);

            if ($user->image && file_exists(public_path($user->image))) {
                unlink(public_path($user->image));
            }

            $user->image = '/uploads/users/' . $imageName;
        }

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->username = $request->username;
        $user->bio = $request->bio;
        $user->save();

        if ($request->password) {
            DB::table('sessions')->where('user_id', auth('adminPanel')->user()->id)->delete();
        }


        return response()->json('success');
    }

    // start notifications

    public function notifications(User $user)
    {
        $users_notifications = DB::table('notification_manage_users')->where('user_id', $user->id)->get();
        $users_notification_ids = [];
        foreach ($users_notifications as $users_notification) {
            $users_notification_ids[] = $users_notification->notification_manage_id;
        }
        $notifications = NotificationManage::whereIn('id', $users_notification_ids)->where('private', 'user')->paginate(20);

        return view('back.users.notifications.index', compact('notifications', 'user'));
    }
    public function notification_create(User $user)
    {
        return view('back.users.notifications.create', compact('user'));
    }
    public function notification_store(User $user, Request $request)
    {
        $this->validate($request, [
            'message'         => 'required',
        ]);

        $notification = new NotificationManage();
        $notification->admin_id = Auth::guard('adminPanel')->id();
        $notification->title = $request->title;
        $notification->message = $request->message;
        $notification->private = 'user';
        $notification->priority = $request->priority;
        $notification->popup = $request->popup ? true : false;
        $notification->save();

        $notification->users()->attach($user);

        session()->put('toast-success', 'اعلان با موفقیت ایجاد شد.');
        return response("success");
    }
    public function notification_show(User $user, NotificationManage $notification)
    {
        return view('back.users.notifications.show', compact('notification', 'user'));
    }
    public function notification_update(User $user, NotificationManage $notification, Request $request)
    {
        $this->validate($request, [
            'message'         => 'required',
        ]);

        $notification->admin_id = Auth::guard('adminPanel')->id();
        $notification->title = $request->title;
        $notification->message = $request->message;
        $notification->private = 'user';
        $notification->priority = $request->priority;
        $notification->popup = $request->popup ? true : false;
        $notification->save();

        session()->put('toast-success', 'اعلان با موفقیت ویرایش شد.');
        return response("success");
    }
    // end notifications

    private function exportExcel($users, Request $request)
    {
        return Excel::download(new UsersExport($users, $request), 'users.xlsx');
    }

    private function exportPrint($users, Request $request)
    {
        //
    }
}
