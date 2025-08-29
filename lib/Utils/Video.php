<?php

declare(strict_types=1);

namespace Ynamite\Massif\Utils;

class Video
{
  /**
   * Generate an embed iframe for a YouTube or Vimeo video.
   *
   * @param string|int $subject The video URL or ID.
   * @param array $params Additional parameters for the video URL.
   * @param array $allow Additional allow attributes for the iframe.
   * @param bool $allowFullScreen Whether to allow fullscreen in the iframe.
   * 
   * @return string The embed iframe HTML.
   */
  public static function embed(string $subject, array $params = [], array $allow = [], bool $allowFullScreen = true): string
  {

    $_params = [
      'youtube' => ["rel" => 0, "enablejsapi" => 1, "iv_load_policy" => 3, "showinfo" => 0],
      'vimeo' => ["color" => 'eca400', "byline" => true, "portrait" => true, "title" => true, "quality" => '1080p']
    ];
    $_allow = [
      'youtube' => ["accelerometer", "encrypted-media", "gyroscope", "picture-in-picture"],
      'vimeo' => ["autoplay", "fullscreen"],
    ];

    if (is_int(intval($subject))) {
      $subject = 'https://vimeo.com/' . $subject;
    }

    $match = self::parseUrl($subject);
    $type = $match['video'];
    $params = http_build_query(array_merge($_params[$type], $params));

    $src = self::buildUrl($match['videoID'], $type);

    $allow = implode('; ', array_merge($_allow[$type], $allow));
    //print_r($match);
    $iframe = '<div class="youtube-scalar"><iframe src="' . $src . '" frameborder="0" allow="' . $allow . '"' . ($allowFullScreen ? ' allowfullscreen' : '') . '></iframe></div>';
    //rex_var_dumper::dump($iframe);
    return $iframe;
  }

  /**
   * Parse a video URL and return its components.
   *
   * @param string $subject The video URL or ID
   * .
   * @return array The parsed video components.
   */
  public static function parseUrl(string $subject): array
  {
    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $subject, $match)) {
      $video_id = $match[1];
      return array("video" => "youtube", "videoID" => $video_id);
    }
    $regexstr = '~
		# Match Vimeo link and embed code
		(?:<iframe [^>]*src=")? 	# If iframe match up to first quote of src
		(?:				# Group vimeo url
			https?:\/\/		# Either http or https
			(?:[\w]+\.)*		# Optional subdomains
			vimeo\.com		# Match vimeo.com
			(?:[\/\w]*\/videos?)?	# Optional video sub directory this handles groups links also
			\/			# Slash before Id
			([0-9]+)		# $1: VIDEO_ID is numeric
			[^\s]*			# Not a space
		)				# End group
		"?				# Match end quote if part of src
		(?:[^>]*></iframe>)?		# Match the end of the iframe
		(?:<p>.*</p>)?		        # Match any title information stuff
		~ix';
    if (preg_match($regexstr, $subject, $match)) {
      $video_id = $match[1];
      return ["video" => "vimeo", "videoID" => $video_id];
    }
    return [];
  }

  public static function buildUrl(string $videoId, string $type): string
  {
    if (!$videoId || !$type) return '';
    if ($type == 'youtube') {
      return 'https://www.youtube.com/embed/' . $videoId;
    } else if ($type == 'vimeo') {
      return 'https://player.vimeo.com/video/' . $videoId;
    } else return '';
  }
}
