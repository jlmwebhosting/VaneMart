<?php
return array(
  // jpg/jpeg, png, gif. JPEG typically offers the best size/quality ratio for thumbs.
  'type'                  => 'jpeg',
  // 0-100 (inclusive). For JPEG this is lossy factor, for PNG - compression strength.
  'quality'               => 75,

  'watermark'             => array(
    'count'               => 2,
    'file'                =>
      head(glob( Bundle::path('vanemart').'config/watermark.{png,jpg,jpeg,gif}', GLOB_BRACE )),
    // this has no effect for count > 1 as watermarks are distributed evenly across Y axis.
    'y'                   => 0.5,
    'x'                   => 0.5,
  ),

  'widthMin'              => 1,
  'widthMax'              => 1200,
  'heightMin'             => 1,
  'heightMax'             => 1200,
  'step'                  => 50,

  'remoteCacheTTL'        => 86400,
);