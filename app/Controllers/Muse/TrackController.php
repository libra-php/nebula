<?php

namespace App\Controllers\Muse;

use App\Models\Track;
use Nebula\Framework\Controller\Controller;
use StellarRouter\Get;

class TrackController extends Controller
{
	#[Get("/track/{uuid}", "track.uuid", ["api"])]
	public function track(string $uuid): ?array
	{
		$track = Track::findByAttribute("uuid", $uuid);
		if ($track) {
			return [
				"uuid" => $track->uuid,
				"src" => "/track/stream/$uuid",
				"title" => $track->meta()->title,
				"artist" => $track->meta()->artist,
				"album" => $track->meta()->album,
				"cover" => "/img/no-album.png",
			];
		}
	}

	#[Get("/track/stream/{uuid}", "track.stream")]
	public function stream(string $uuid): void
	{
		$track = Track::findByAttribute("uuid", $uuid);
		if ($track && file_exists($track->name)) {
			$meta = $track->meta();
			header("Content-Type: {$meta->mime_type}");
			header("Content-Length: " . filesize($track->name));
			header("Accept-Ranges: bytes");
			header("Content-Transfer-Encoding: binary");
			readfile($track->name);
			exit;
		}
	}

	#[Get("/track/play/{uuid}", "track.play")]
	public function play(string $uuid): string
	{
		return template("muse/player/audio.php", [
			"src" => "/track/stream/$uuid",
		]);
	}
}