<?php

namespace App\Controllers\Blog;

use App\Models\Post;
use Nebula\Controller\Controller;
use StellarRouter\{Get, Group};

#[Group(prefix: "/blog")]
class BlogController extends Controller
{
    #[Get("/", "blog.index")]
    public function index(): string
    {
        $posts = Post::search(["status", "Published"]);
        return latte("blog/index.latte", [
            "posts" => $posts ?? [],
        ]);
    }

    #[Get("/{year}/{month}/{slug}", "blog.show")]
    public function show(string $year, string $month, string $slug): string
    {
        $post = Post::search(
            ["status", "Published"],
            ["YEAR(published_at)", "<=", $year],
            ["MONTH(published_at)", "<=", $month],
            ["slug", $slug]
        );
        if ($post) {
            // The current date must be greater than the published date
            if (date("Y-m-d H:i:s") >= $post->published_at) {
                return latte("blog/show.latte", [
                    "post" => $post,
                ]);
            }
        }
        return latte("blog/not-found.latte");
    }

    #[Get("/{year}/{month}/{slug}/part", "blog.show.part", ["push-url"])]
    public function show_part(string $year, string $month, string $slug): string
    {
        $post = Post::search(
            ["status", "Published"],
            ["YEAR(published_at)", "<=", $year],
            ["MONTH(published_at)", "<=", $month],
            ["slug", $slug]
        );
        if ($post) {
            // The current date must be greater than the published date
            if (date("Y-m-d H:i:s") >= $post->published_at) {
                return latte("blog/show.latte", [
                    "post" => $post,
                ]);
            }
        }
        return latte("blog/not-found.latte", [], "content");
    }
}
