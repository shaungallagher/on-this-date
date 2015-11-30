<?php

// ON THIS DATE IN FACEBOOK LAND
// -----------------------------
// Displays a list of posts that you created on Facebook
// on this date in previous years.

include_once 'facebook-php-sdk/src/facebook.php';

$facebook = new Facebook(array(
  'appId'  => '', // your app ID
  'secret' => '' // your secret
));

// See if there is a user from a cookie
$user = $facebook->getUser();

$access_token = $facebook->getAccessToken();
$params = array('access_token' => $access_token);

//Login URL
$loginUrl = $facebook->getLoginUrl(
            array(
                'scope'         => 'read_stream,export_stream',
                'redirect_uri'  => 'https://.../onthisdate/' // script location
            )
);

//Logout URL
$logoutUrl  = $facebook->getLogoutUrl();

if ($user) {
   try {
      $user_profile = $facebook->api('/me', 'GET', $params);
   } catch (FacebookApiException $e) {
      $user = null;
   }
}

if ($user) {

   ?>

   <style>

   .post {
      background-color: #F5F5F5;
      border: 1px solid #E9E9E9;
      padding: 15px;
      margin: 0 15px 15px 15px;
      font-size: 15px;
      font-family: arial;
      line-height:24px;
   }

   .post a {
      color: #02A;
      font-weight:bold;
      text-decoration:none;
   }

   h3 {
      margin: 15px;
      font-family: arial;
   }

   h3:not(:first-child) {
      margin-top: 40px;
   }

   .linkbox,
   .comment {
      background-color: #FFF;
      padding: 8px;
      margin-bottom:8px;
   }

   .comment {
      background-color: #EEE;
   }

   </style>

   <?

   $timestamp = time();

   // Optionally provide a date other than today's date as a URL param.
   if ($_REQUEST['date']) {
      $timestamp = strtotime($_REQUEST['date']);
   }

   // Cycle backward through the years.
   for ($yy = date("Y") - 1; $yy > 2005; $yy--) {
      $today = date('m-d', $timestamp);
      $tomorrow = date('m-d', $timestamp+86400);
      $query = '/me/feed/?since='.$yy.'-'.$today.'&until='.$yy.'-'.$tomorrow.'&access_token='.$access_token;
      $data = $facebook->api($query);

      if (count($data['data']) > 0) {
         echo '<h3>On this date in '.$yy.' ...</h3>';
      }

      foreach ($data['data'] as $key => $post) {
         $id = explode("_", $post[id]);
         $href = 'http://www.facebook.com/'.$id[0].'/posts/'.$id[1];
         if ($post[message]) {
            $message = $post[message];
         } else if ($post[story]) {
            $message = $post[story];
         }
         $message = str_replace("\n\n","<br><br>", $message);
         $thetimestamp = strtotime(str_replace("T", " ", $post[created_time]));
         $thedate = date("M. j, Y", $thetimestamp);
         $thetime = date("g:i a", $thetimestamp);

         echo '<div class=post>';
         echo '<p><b>'.$thedate.' at '.$thetime.'</b> &nbsp; <a href="'.$href.'" target="_new">View post on Facebook</a></p>';
         echo '<p>'.$message.'</p>';

         // Show attachment
         if ($post[link]) {
            echo '<div class="linkbox">';
            if ($post[picture]) {
               echo '<p><img src="'.$post[picture].'"></p>';
            }
            echo '<p><a href="'.$post[link].'">'.$post[name].'</a></p>';
            echo '<p>'.$post[description].'</p>';
            echo '</div>';
         }

         // Show likes
         if ($post[likes] && count($post[likes][data]) > 0) {
            echo '<p><b>Likes:</b> ';
            $likes_array = array();
            foreach ($post[likes][data] as $key2 => $like) {
               $likes_array[] = $like[name];
            }
            echo implode(', ', $likes_array);
            echo '</p>';
         }

         // Show comments
         if ($post[comments] && count($post[comments][data]) > 0) {
            foreach ($post[comments][data] as $key2 => $comment) {
               echo '<div class="comment">';
               echo '<p><b>'.$comment[from][name].': </b> '.$comment[message].'</p>';
               echo '</div>';
            }
         }

         echo '</div>';
      }
   }
   exit();
}

?>
<!DOCTYPE html>
<html xmlns:fb="http://www.facebook.com/2008/fbml">
  <body>
    <fb:login-button autologoutlink="true" scope="read_stream,export_stream"></fb:login-button>
    <div id="fb-root"></div>
    <script>
      window.fbAsyncInit = function() {
        FB.init({
          appId: '<?php echo $facebook->getAppID() ?>',
          cookie: true,
          xfbml: true,
          oauth: true
        });
        FB.Event.subscribe('auth.login', function(response) {
          window.location.reload();
        });
        FB.Event.subscribe('auth.logout', function(response) {
          window.location.reload();
        });
      };
      (function() {
        var e = document.createElement('script'); e.async = true;
        e.src = document.location.protocol +
          '//connect.facebook.net/en_US/all.js';
        document.getElementById('fb-root').appendChild(e);
      }());
    </script>
  </body>
</html>