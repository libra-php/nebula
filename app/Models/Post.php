<?php

namespace App\Models;

use Carbon\Carbon;
use Nebula\Model\Model;

final class Post extends Model
{
    public string $table = "posts";
    public string $primary_key = "id";

    protected array $guarded = ["id", "created_at", "updated_at"];

    public function __construct(protected ?string $id = null)
    {
    }

    public function author(): ?User
    {
        return User::find($this->user_id);
    }

    public function publishedAt(): string
    {
        return Carbon::createFromDate(
            $this->published_at
        )->toFormattedDateString();
    }

    public function showLink(): string
    {
        $year = date('Y', strtotime($this->published_at));
        $month = date('m', strtotime($this->published_at));
        return buildRoute("blog.show", $year, $month, $this->slug);
    }

    public function showPartLink(): string
    {
        $year = date('Y', strtotime($this->published_at));
        $month = date('m', strtotime($this->published_at));
        return buildRoute("blog.show.part", $year, $month, $this->slug);
    }

    public function bannerLink(): ?string
    {
        if (!$this->banner_image) {
            return null;
        }
        $basename = basename($this->banner_image);
        $public_uploads = config("paths.public_uploads");
        return sprintf("%s/%s", $public_uploads, $basename);
    }

    /**
    * Calculates approximate read time
    */
    public function calculateReadTime(int $wpm = 200): int
    {
        // Remove HTML tags (if any)
        $cleanContent = strip_tags($this->content);

        // Count the number of words in the content
        $wordCount = str_word_count($cleanContent);

        // Calculate read time in minutes
        $readTimeMinutes = $wordCount / $wpm;

        // Round up to the nearest minute
        $readTimeMinutes = round($readTimeMinutes);

        return $readTimeMinutes;
    }

}
