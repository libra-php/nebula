<?php

namespace App\Controllers\Blog;

use App\Models\Post;
use Nebula\Controller\Controller;
use StellarRouter\{Get, Group};

#[Group(prefix: "/blog")]
class BlogController extends Controller
{
    private function getPosts(): array
    {
        $posts = Post::search(["status", "Published"]);
        if ($posts instanceof Post) $posts = [$posts];
        return $posts ?? [];
    }

    private function getPost(string $year, string $month, string $slug)
    {
        return Post::search(
            ["status", "Published"],
            ["YEAR(published_at)", "<=", $year],
            ["MONTH(published_at)", "<=", $month],
            ["slug", $slug]
        );
    }


    #[Get("/", "blog.index")]
    public function index(): string
    {
        return latte("blog/index.latte", [
            "posts" => $this->getPosts(),
        ]);
    }

    #[Get("/part", "blog.index.part")]
    public function indexPart(): string
    {
        return latte("blog/index.latte", [
            "posts" => $this->getPosts(),
        ], "content");
    }

    #[Get("/{year}/{month}/{slug}", "blog.show")]
    public function show(string $year, string $month, string $slug): string
    {
        $post = $this->getPost($year, $month, $slug);
        if ($post) {
            if (date("Y-m-d H:i:s") >= $post->published_at) {
                return latte("blog/show.latte", [
                    "post" => $post,
                ]);
            }
        }
        return latte("blog/not-found.latte");
    }

    #[Get("/{year}/{month}/{slug}/part", "blog.show.part", ["push-url"])]
    public function showPart(string $year, string $month, string $slug): string
    {
        $post = $this->getPost($year, $month, $slug);
        if ($post) {
            if (date("Y-m-d H:i:s") >= $post->published_at) {
                return latte("blog/show.latte", [
                    "post" => $post,
                ], "content");
            }
        }
        return latte("blog/not-found.latte", [], "content");
    }
}
