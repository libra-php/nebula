<?php

namespace App\Controllers\Blog;

use App\Models\Post;
use Carbon\Carbon;
use Nebula\Controller\Controller;
use StellarRouter\{Get, Group};

#[Group(prefix: "/blog")]
class BlogController extends Controller
{
    private function getBannerImage(?string $banner_image): ?string
    {
        if (!$banner_image) {
            return null;
        }
        $basename = basename($banner_image);
        $public_uploads = config("paths.public_uploads");
        return sprintf("%s/%s", $public_uploads, $basename);
    }

    #[Get("/", "blog.index")]
    public function index(): string
    {
        $posts = Post::search(["status", "Published"]);
        return latte("blog/index.latte", [
            "posts" => $posts,
        ]);
    }

    #[Get("/{year}/{month}/{slug}", "blog.index")]
    public function show(string $year, string $month, string $slug): string
    {
        $post = Post::search(
            ["status", "Published"],
            ["YEAR(published_at)", "<=", $year],
            ["MONTH(published_at)", "<=", $month],
            ["slug", $slug]
        );
        if ($post) {
            // The current date must be greather than the published date
            if (date("Y-m-d H:i:s") >= $post->published_at) {
                return latte("blog/show.latte", [
                    "post" => $post,
                    "banner_image" => $this->getBannerImage(
                        $post->banner_image
                    ),
                    "published_at" => Carbon::createFromDate(
                        $post->published_at
                    )->toFormattedDateString(),
                ]);
            }
        }
        return latte("blog/not-found.latte");
    }

    #[Get("/preview/{post}", "blog.preview", ["auth"])]
    public function preview(string $post): string
    {
        $post = Post::find($post);
        if ($post) {
            $uri = "/blog/preview/{$post->id}";
            header("HX-Push-Url: $uri");
            return latte("blog/preview.latte", [
                "post" => $post,
                "banner_image" => $this->getBannerImage($post->banner_image),
                "published_at" => Carbon::createFromDate(
                    $post->published_at
                )->toFormattedDateString(),
            ]);
        }
        return latte("blog/not-found.latte");
    }
}
