<?php

namespace App\Modules;

use App\Models\Post;
use Nebula\Backend\Module;

class Blog extends Module
{
    public function __construct()
    {
        $this->name_col = "title";
        $this->export_csv = false;
        $this->table_columns = [
            "posts.id" => "ID",
            "posts.title" => "Title",
            "users.name" => "Author",
            "posts.published_at" => "Published At",
            "posts.updated_at" => "Updated At",
            "posts.created_at" => "Created At",
        ];
        $this->filter_links = [
            "Draft" => "status = 'Draft'",
            "Published" => "status = 'Published'",
        ];
        $this->search = ["title"];
        $this->filter_datetime = "created_at";
        $this->joins = ["INNER JOIN users ON posts.user_id = users.id"];

        $this->form_columns = [
            "banner_image" => "Banner Image",
            "user_id" => "Author",
            "status" => "Status",
            "published_at" => "Publish Date",
            "slug" => "Slug",
            "title" => "Title",
            "subtitle" => "Subtitle",
            "content" => "Post Content",
        ];
        $this->validation = [
            "title" => ["required"],
            "user_id" => ["required"],
            "published_at" => ["required"],
            "slug" => ["required"],
        ];
        $this->form_controls = [
            "banner_image" => "image",
            "user_id" => "select",
            "status" => "select",
            "slug" => "input",
            "published_at" => "datetime",
            "title" => "input",
            "subtitle" => "input",
            "content" => "editor",
        ];
        $this->form_defaults = [
            "published_at" => date("Y-m-d H:i:s"),
        ];
        $this->select_options = [
            "user_id" => db()->selectAll(
                "SELECT id, name
                FROM users
                WHERE id = ?",
                user()->id
            ),
            "status" => [
                option("Draft", "Draft"),
                option("Published", "Published"),
            ],
        ];
        $this->addRowAction("preview_post", "Preview");

        parent::__construct("blog");
    }

    protected function processTableRequest(): void
    {
        parent::processTableRequest();
        if (request()->has("preview_post")) {
            $post = Post::find(request()->id);
            if ($post) {
                $route = buildRoute("blog.preview", $post->id);
                redirect($route);
                exit();
            }
        }
    }
}
