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
        $this->filter_datetime = "created_at";
        $this->joins = ["INNER JOIN users ON posts.user_id = users.id"];

        $this->form_columns = [
            "user_id" => "Author",
            "published_at" => "Publish Date",
            "slug" => "Slug",
            "title" => "Title",
            "subtitle" => "Subtitle",
            "content" => "Post Content",
        ];
        $this->validation = [
            "title" => ["required"],
            "user_id" => ["required"],
        ];
        $this->form_controls = [
            "user_id" => "select",
            "slug" => "input",
            "published_at" => "datetime",
            "title" => "input",
            "subtitle" => "input",
            "content" => "editor",
        ];
        $this->select_options = [
            "user_id" => db()->selectAll("SELECT id, name FROM users ORDER BY name"),
        ];
        $this->addRowAction(
            "preview_post",
            "Preview",
        );

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
                exit;
            }
        }
    }
}
