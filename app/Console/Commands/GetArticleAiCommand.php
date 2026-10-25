<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
// فاز ۷۶ — ایمپورت Intervention حذف شد (فاساد v2 در vendor v3 وجود ندارد)

class GetArticleAiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:article_ai';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $contents=Post::where(['created_by'=>'ai-pro','status'=>'waiting'])->get();

        foreach ($contents as $content){

            $response= Http::get('https://ai.webtpro.ir/api/get-posts', [
                'token_key'=>option('AI_TOKEN_KEY'),
                'slug'=> $content->slug,
            ]);

            if ($response->body()!="{}"){
                $data=json_decode($response->body());

                if (@$data->image){
                    if (!file_exists(public_path("/uploads/posts"))) {
                        Storage::disk('public')->makeDirectory("/uploads/posts");
                    }
                    if ($content->image==null){
                        $image_url=$data->image;

                        $headers_url = @get_headers($image_url);

                        if($headers_url && strpos( $headers_url[0], '200')) {
                            $imageName='ai-pro-'.uniqid().'.jpg';
                            $localFileName=public_path("/uploads/posts/".$imageName);
                            file_put_contents($localFileName, fopen($image_url, 'r'));
                            // فاز ۷۶ — مقیاس با GD خالص (بدون Intervention)
                            $this->shrinkWithGd($localFileName, 1024);
                            $content->image='/uploads/posts/'.$imageName;
                        }

                    }

                }
                if (@$data->images){
                    if (!file_exists(public_path("/uploads/posts"))) {
                        Storage::disk('public')->makeDirectory("/uploads/posts");
                    }
                    if ($data->status!="waiting"){
                        $images=explode('[|]',$data->images);
                        $image=[];
                        foreach ($images as $img) {
                            $image_url = $img;
                            $headers_url = @get_headers($image_url);
                            if ($headers_url && strpos($headers_url[0], '200')) {
                                $imageName = 'ai-pro-' . uniqid() . '.jpg';
                                $localFileName = public_path("/uploads/posts/" . $imageName);
                                file_put_contents($localFileName, fopen($image_url, 'r'));
                                // فاز ۷۶ — مقیاس با GD خالص (بدون Intervention)
                                $this->shrinkWithGd($localFileName, 1024);

                                $image[] = "<img src=" . asset('/uploads/posts/' . $imageName) . " width='20%'>";

                            }
                        }
                        $image = implode(" ", $image);
                        $content->content = $data->content . ' ' . $image;
                    }else{
                        $content->content=$data->content;
                    }

                }else{
                    $content->content=$data->content;
                }

                $content->meta_title=$data->meta_title;
                $content->meta_description=$data->meta_description;
                if ($data->status=="end"){
                    $content->status="end";
                }
                if ($data->status=="error"){
                    $content->status="error";
                }
                $content->save();
            }


        }
    }

    /**
     * فاز ۷۶ — کوچک‌سازی تصویر با GD خالص (فقط کاهش — بدون هیچ کلاسی از Intervention)
     *
     * چرا: `Image::make()->resize()->save()` به فاساد v2 وابسته بود که در vendor v3
     * وجود ندارد؛ روی هاست با vendor مخلوط هم autoload کلاس‌های Intervention کرش
     * غیرقابل‌شکار می‌دهد. خرابی این مرحله تصویر خام را نگه می‌دارد (fail-safe).
     */
    private function shrinkWithGd(string $path, int $maxWidth): void
    {
        try {
            $binary = (string) @file_get_contents($path);

            if ($binary === '' || ! function_exists('shop_gd_fallback_encode')) {
                return;
            }

            [$encoded] = shop_gd_fallback_encode($binary, 'jpg', 90, $maxWidth, null);

            if ($encoded !== null) {
                file_put_contents($path, $encoded);
            }
        } catch (\Throwable) {
            // مقیاس اختیاری است — تصویر خام به همان شکل می‌ماند
        }
    }
}
