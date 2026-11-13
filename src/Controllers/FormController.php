<?php

namespace Themes\SaniarTheme\src\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class FormController extends Controller
{
    public function show($slug)
    {
        $form = Form::where('slug', $slug)
            ->where('published', true)
            ->with('fields')
            ->firstOrFail();

        return view('front::forms.show', compact('form'));
    }


    public function submit(Request $request, Form $form)
    {
        // ساخت قوانین اعتبارسنجی
        $rules = [];
        $customMessages = [];
        $has_photo=false;
        foreach ($form->fields as $field) {
            $fieldRules = [];

            // اضافه کردن required
            if ($field->required) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            // اضافه کردن قوانین اختصاصی
            if (!empty($field->rules_validation)) {
                $customRules = explode('|', $field->rules_validation);
                $fieldRules = array_merge($fieldRules, $customRules);
            }

            // اضافه کردن قوانین بر اساس نوع
            if ($field->type === 'email') {
                $fieldRules[] = 'email';
            }

            if ($field->type === 'url') {
                $fieldRules[] = 'url';
            }

            if ($field->type === 'tel') {
                $fieldRules[] = 'regex:/^09[0-9]{9}$/';
                $customMessages[$field->name . '.regex'] = 'شماره موبایل معتبر نیست';
            }

            if ($field->type === 'number') {
                $fieldRules[] = 'numeric';
            }

            // حذف موارد تکراری و خالی
            $fieldRules = array_filter(array_unique($fieldRules));
            $rules[$field->name] = implode('|', $fieldRules);

            // پیام‌های خطای سفارشی
            if ($field->required) {
                $customMessages[$field->name . '.required'] = "فیلد {$field->label} الزامی است";
            }

            if (!empty($field->rules_validation) && str_contains($field->rules_validation, 'min:')) {
                preg_match('/min:(\d+)/', $field->rules_validation, $matches);
                if (isset($matches[1])) {
                    $customMessages[$field->name . '.min'] = "فیلد {$field->label} باید حداقل {$matches[1]} کاراکتر باشد";
                }
            }

            if (!empty($field->rules_validation) && str_contains($field->rules_validation, 'max:')) {
                preg_match('/max:(\d+)/', $field->rules_validation, $matches);
                if (isset($matches[1])) {
                    $customMessages[$field->name . '.max'] = "فیلد {$field->label} باید حداکثر {$matches[1]} کاراکتر باشد";
                }
            }
        }

        // اعتبارسنجی
        $validator = Validator::make($request->all(), $rules, $customMessages);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'message' => 'خطای اعتبارسنجی رخ داده است',
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        // ذخیره داده‌ها
        $data = [];
        foreach ($form->fields as $field) {
            $value = $request->{$field->name};

            // پردازش فایل
            if ($field->type === 'file' && $request->hasFile($field->name)) {
                $file = $request->file($field->name);
                $path = $file->store('uploads/form-files', 'public');
                $value = $path;
                $has_photo=true;
            }

            $data[$field->name] = $value;
        }
        $data=json_encode($data);

        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'data' => $data,
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'has_photo' => $has_photo,
            'submitted_at' => now(),
        ]);

        // ارسال ایمیل اعلان (اختیاری)
        if ($form->email_notify) {
            // Mail::to($form->email_notify)->send(new FormSubmittedNotification($form, $submission));
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $form->success_message ?? 'فرم با موفقیت ارسال شد'
            ]);
        }

        return redirect()->back()->with('success', $form->success_message);
    }
}
