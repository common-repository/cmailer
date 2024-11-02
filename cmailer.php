<?php

define('CM_VERSION','0.1.5.2');

/*
Plugin Name: CommentMailer
Plugin URI: http://quietschbunt.wordpress.com/commentmailer
Description: CommentMailer benachrichtigt LeserInnen von neuen Kommentaren zu einem abonnierten Weblog-Posting.
Author: Sebastian Schwaner
Author URI: http://quietschbunt.wordpress.com/
Version: 0.1.5.2
*/


/* ----------------------------------------------------------------------------

© Copyright 2006  Sebastian Schwaner

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

---------------------------------------------------------------------------- */

# --- ADD FILTERS AND ACTIONS
add_action('admin_menu','cm_setGUI');
add_action('comment_post','cm_execute');
add_action('wp_set_comment_status','cm_moderation');

# --- FUNCTIONS

function cm_setGUI() {
   add_submenu_page( 'edit-comments.php','CommentMailer','CommentMailer',9,__FILE__,'cm_GUI' );
}

// BASIC FUNCTION FOR THE COMMENTS.PHP TEMPLATE

function setCommentMailer( $get ) {

     global $wpdb,$user_ID;
     $table = $wpdb->prefix."cmailer";

     // VERIFY SUBSCRIBING
     if( @$get['cmd'] == 'verify' ) :
          $post_id = @$get['post_id'];
          $email = @$get['email'];

          if( ! $post_id || ! $email ) :
               echo "<script type=\"text/javascript\">alert('Fehler beim Verifizieren!');</script>";
          else :
               $wpdb->query( $wpdb->prepare("UPDATE $table SET status='OK' WHERE md5(post_id)=%s AND md5(email)=%s",$post_id,$email) );
               echo "<script type=\"text/javascript\">alert('Vielen Dank für die Bestätigung! Du erhälst nun Benachrichtigungen bei neuen Kommentaren!');</script>";
          endif;
     endif;

     // UNSUBSCRIBE
     if( @$get['cmd'] == 'unsubscribe' ) :
          if( ! $id = @$get['id'] ) :
               echo "<script type=\"text/javascript\">alert('Fehler beim Löschen des Abonnements!\nBitte informiere den Support dieses Blogs!');</script>";
          else :
               $wpdb->query( $wpdb->prepare("DELETE FROM $table WHERE md5(id)=%s",$id) );
               echo "<script type=\"text/javascript\">alert('Austragen erfolgreich! Du erhälst bei neuen Kommentaren keine weiteren E-Mail-Benachrichtigungen!');</script>";
          endif;

     endif;

     if( ! $user_ID ) :
          echo "<p><input type=\"checkbox\" name=\"comment_notify\" value=\"1\"> Ja, ich möchte bei Kommentaren benachrichtigt werden!</p>";
     endif;
}

function cm_moderation( $id ) {
     global $wpdb;

     // CHECKING POST_ID
     if( preg_match("/[a-zA-z]/",$id) ) die("Fehler-ID: #001 / Fehlerhafte Kommentar-ID!");

     // RETRIEVE COMMENT'S NEW STATUS
     $status = $wpdb->get_var( $wpdb->prepare("SELECT comment_approved FROM $wpdb->comments WHERE comment_ID=%d",$id));

     // IF STATUS CHANGED TO APPROVED
     if(  $status == 1 ) :

          $table = $wpdb->prefix."cmailer";
          $opt = get_option("CM_OPTIONS");
          $url = get_option("home");

          // LOAD FROM SETUP
          if( ! $from = $opt['from'] ) die;
          $extra = $opt['extra'];

          // GET COMMENT DATA
          if( ! $coms = $wpdb->get_results( $wpdb->prepare("SELECT comment_post_ID,comment_approved,comment_author,comment_author_email,comment_date,comment_content FROM $wpdb->comments WHERE comment_ID=%d LIMIT 0,1",$id) ) ) 
               die("Fehler-ID: #002 / Kommentar-Daten konnten nicht geladen werden!");

          // SPLITTING DATA
          $email    = $coms[0]->comment_author_email;
          if( ! preg_match('/^[^@\s]+@([-a-z0-9]+\.)+[a-z]{2,}$/i',$email) ) die("Fehler-ID: #006 / Ungültige Autoren-eMail!");

          $nick     = mysql_real_escape_string($coms[0]->comment_author);
          $date     = date("m.d.Y G:i",strtotime($coms[0]->comment_date));
          $content  = mysql_real_escape_string($coms[0]->comment_content);
          $post_id  = $coms[0]->comment_post_ID;
          $approval = $coms[0]->comment_approved;

          // RETRIEVING TITLE
          if( ! $title = $wpdb->get_var( $wpdb->prepare("SELECT post_title FROM $wpdb->posts WHERE ID=%d",$post_id) ) )
               die("Fehler-ID: #003 / Kein gültiger Beitrag!");

          // GET ALL SUBSCRIBERS FROM DATABASE
          $subs = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $table WHERE post_id=%s AND status='ok' AND email != %s",$post_id,$email) );

          // DO ONLY IF COMMENT IS APPROVED AND IF SUBSCRIPTIONERS FOUND
          if( $approval && $subs ) :
               $adr = array();
               $msg = "Dies ist eine automatisch generierte Kommentar-Benachrichtigung von $url\n-----------------------------------------------------------------------------------------\n\nHallo,\n\nzu dem Beitrag \"$title\" ist ein neuer Kommentar geschrieben worden.\n\n$nick schrieb am $date Uhr:\n\"$content\"\n\nDen Beitrag \"$title\" findest Du hier:\n$url/?p=$post_id\n\nDen Original-Kommentar findest Du hier:\n$url/?p=$post_id#comment-$id\n\nWenn Du keine weiteren Benachrichtigungen zu diesem Beitrag erhalten möchtest, klicke zum Abmelden bitte auf den folgenden Link:\n$url/?p=$post_id&cmd=unsubscribe&id=".md5($subs->id)."\n\nVielen Dank.\n\n";
               foreach( $subs as $subs ) array_push( $adr,$subs->email );
               $bcc = implode(",",$adr);
               mail("","Ein neuer Kommentar wurde abgegeben",$msg,"From: $from\nBCC: $bcc\nContent-type: text/plain; charset=UTF-8\n","$extra");
          endif;

     endif;

}

function cm_execute( $id ) {

     global $wpdb;

     // CHECKING POST_ID
     if( preg_match("/[a-zA-z]/",$id) ) die("Fehler-ID: #001 / Fehlerhafte Kommentar-ID!");

     $table = $wpdb->prefix."cmailer";
     $opt = get_option("CM_OPTIONS");
     $url = get_option("home");

     // LOAD FROM SETUP
     if( ! $from = $opt['from'] ) die;
     $extra = $opt['extra'];

     // GET COMMENT DATA
     if( ! $coms = $wpdb->get_results( $wpdb->prepare("SELECT comment_post_ID,comment_approved,comment_author,comment_author_email,comment_date,comment_content FROM $wpdb->comments WHERE comment_ID=%d LIMIT 0,1",$id) ) ) 
          die("Fehler-ID: #002 / Kommentar-Daten konnten nicht geladen werden!");

     // SPLITTING DATA
     $email    = $coms[0]->comment_author_email;
     if( ! preg_match('/^[^@\s]+@([-a-z0-9]+\.)+[a-z]{2,}$/i',$email) ) die("Fehler-ID: #006 / Ungültige Autoren-eMail!");

     $nick     = mysql_real_escape_string($coms[0]->comment_author);
     $date     = date("m.d.Y G:i",strtotime($coms[0]->comment_date));
     $content  = mysql_real_escape_string($coms[0]->comment_content);
     $post_id  = $coms[0]->comment_post_ID;
     $approval = $coms[0]->comment_approved;

     // RETRIEVING TITLE
     if( ! $title = $wpdb->get_var( $wpdb->prepare("SELECT post_title FROM $wpdb->posts WHERE ID=%d",$post_id) ) )
          die("Fehler-ID: #003 / Kein gültiger Beitrag!");

     // GET ALL SUBSCRIBERS FROM DATABASE
     $subs = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $table WHERE post_id=%s AND status='ok' AND email != %s",$post_id,$email) );

     // DO ONLY IF COMMENT IS APPROVED AND IF SUBSCRIPTIONERS FOUND
     if( $approval && $subs ) :
          $adr = array();
          $msg = "Dies ist eine automatisch generierte Kommentar-Benachrichtigung von $url\n-----------------------------------------------------------------------------------------\n\nHallo,\n\nzu dem Beitrag \"$title\" ist ein neuer Kommentar geschrieben worden.\n\n$nick schrieb am $date Uhr:\n\"$content\"\n\nDen Beitrag \"$title\" findest Du hier:\n$url/?p=$post_id\n\nDen Original-Kommentar findest Du hier:\n$url/?p=$post_id#comment-$id\n\nWenn Du keine weiteren Benachrichtigungen zu diesem Beitrag erhalten möchtest, klicke zum Abmelden bitte auf den folgenden Link:\n$url/?p=$post_id&cmd=unsubscribe&id=".md5($subs->id)."\n\nVielen Dank.\n\n";
          foreach( $subs as $subs ) array_push( $adr,$subs->email );
          $bcc = implode(",",$adr);
          mail("","Ein neuer Kommentar wurde abgegeben",$msg,"From: $from\nBCC: $bcc\nContent-type: text/plain; charset=UTF-8\n","$extra");
     endif;

     // SUBSCRIBE USER IF WISHED
     if( @$_POST['comment_notify'] == '1' ) :

          // ALWAYS SUBSCRIBED TO THIS POSTING?
          if( ! $wpdb->get_var( $wpdb->prepare(" SELECT id FROM $table WHERE email=%s AND post_id=%d",$email,$post_id) ) ) :

               // CHECK IF EMAIL EXISTS IN DB
               $status = $wpdb->get_var( $wpdb->prepare("SELECT id FROM $table WHERE email=%s AND status='ok'",$email) );

               // IF EMAIL DOESNT EXIST IN DB SEND VERIFYING REQUEST
               if( ! $status ) : 
                    // WRITE SUBSCRIBER TO DB
                    $wpdb->query( $wpdb->prepare(" INSERT INTO $table VALUES ('','%s','%s','%d','wait')",$nick,$email,$post_id) );

                    // PREPARING MESSAGE
                    $msg = "Hallo $nick,\n\nDu hast Dich dazu entschieden, zu dem Posting \"$title\" auf $url per Nachricht über neue Kommentare informiert zu werden. Um sicher zu gehen, dass dies wirklich Dein Wunsch ist und die angegebene E-Mail auch zu Dir gehört, klicke bitte auf den folgenden Link, um dies zu bestätigen:\n$url/?p=$post_id&cmd=verify&post_id=".md5($post_id)."&email=".md5($email)."\n\nOhne diese Bestätigung wirst Du keine Benachrichtigungen erhalten.\n\nVielen Dank.";

                    // SEND VERIFY
                    mail($email,"Bestätigung über Kommentar-Benachrichtigung erbeten",$msg,"From: $from\nContent-type: text/plain; charset=UTF-8\n","$extra");
               endif; 

               // IF EMAIL EXISTS IN DB ADD SUBSCRIBER TO DB FOR THE POSTING WITHOUT VERIFYING REQUEST
               if( $status ) :
                    // WRITE SUBSCRIBER TO DB
                    $wpdb->query( $wpdb->prepare(" INSERT INTO $table VALUES ('','%s','%s','%d','ok')",$nick,$email,$post_id) );
               endif;

          else :
               die("Fehler-ID: #007 / Dieser Beitrag ist bereits abonniert!");
          endif;

     endif;
}

// CREATES A TABLE IF TABLE NOT EXISTS
function cm_createTable() {
     global $wpdb;
     $table = $wpdb->prefix."cmailer";

     // DELETING OLD TABLE, CREATING NEW ONE
     $wpdb->query(" DROP TABLE $table ");
     $wpdb->query(" CREATE TABLE $table (id int(5) not null auto_increment, name varchar(100) not null, email varchar(255) not null, post_id bigint(20) not null, status enum('ok','wait') default 'wait' not null, primary key(id)) ");

     // SETTING TABLE VERSION
     $opt['table'] = '0.1.2';
     $opt['from'] = get_option('admin_email');
     update_option('CM_OPTIONS',$opt);
}

// UNSUBSCRIBES A USER FROM POSTING
function cm_unsubscribe() {
     global $wpdb;
     $table = $wpdb->prefix."cmailer";

     if( ! $id = @$_GET['id'] ) :
          echo "<script type=\"text/javascript\">Ungültige Posting-ID!</script>";
          return;
     endif;

     $wpdb->query( $wpdb->prepare("DELETE FROM $table WHERE md5(id) = %s",$id) );
     $wpdb->query(" OPTIMIZE TABLE $table ");
}

// UNSUBSCRIBE ALL USERS
function cm_unsubscribeAll() {
     global $wpdb;
     $table = $wpdb->prefix."cmailer";

     $wpdb->query(" DELETE FROM $table ");
     $wpdb->query(" OPTIMIZE TABLE $table ");
}

// GRAPHICAL OUTPUT
function cm_GUI() {

     global $wpdb;
     $table = $wpdb->prefix."cmailer";

     // CHECKING ENVIRONMENT
     if( ! is_object( $wpdb ) ) die("Fehler-ID: #008 / Grundlegende Fehlfunktion! Objekt nicht gefunden!");
     if( ! method_exists($wpdb,"prepare") ) die("Fehler-ID: #009 / Objektmethode prepare() nicht vorhanden!");
     if( ! method_exists($wpdb,"get_var") ) die("Fehler-ID: #011 / Objektmethode get_var() nicht vorhanden!");
     if( ! method_exists($wpdb,"get_results") ) die("Fehler-ID: #012 / Objektmethode get_results() nicht vorhanden!");
     if( ! method_exists($wpdb,"query") ) die("Fehler-ID: #013 / Objektmethode query() nicht vorhanden!");
     if( ! function_exists("get_option") ) die("Fehler-ID: #010 / Grundlegende Fehlfunktion! Funtion get_option() nicht gefunden!");
     if( ! function_exists("update_option") ) die("Fehler-ID: #014 / Grundlegende Fehlfunktion! Funtion update_option() nicht gefunden!");

     // IF POST OR GET ISSET
     if( @$_POST['cmd'] == 'createTable') cm_createTable();
     if( @$_GET['cmd'] == 'unsubscribe' ) cm_unsubscribe();
     if( @$_POST['cmd'] == 'unsubscribeAll' ) cm_unsubscribeAll();

     // GET TABLE VERSION
     $opt = get_option("CM_OPTIONS");

     // IF NO TABLE FOUND CREATE IT
     if( ! $wpdb->get_var(" DESCRIBE $table ") || $opt['table'] != '0.1.2' ) : ?>
     <div class="wpbody">
          <div class="wrap">
               <form method="post">
               <h2>Initialisiere Datenbank</h2>
               <p>Es scheint das erste Mal zu sein, dass Du dieses Plugin auf Deinem Blog installierst. Eventuell ist jedoch auch die verwendete Datentabelle nicht mehr mit der Version des Plugins kompatibel. Das Anlegen einer neuen Tabelle <b><?php echo $table; ?></b> ist notwendig.</p>
               <p><b>ACHTUNG:</b> Alle Daten werden gelöscht!</p>
               <p><input type="submit" value="Anlegen/Update der Tabelle" class="button-secondary"></p>
               <input type="hidden" name="cmd" value="createTable">
               </form>
          </div>
     </div>

     <?php else : ?>

     <?php // OUTPUT OF ALL SUBSCRIBED POSTINGS ?>
     <?php if( empty($_GET['site']) ) $site = 0; else $site = $_GET['site'] * 10; ?>
     <?php $subs = $wpdb->get_results( $wpdb->prepare(" SELECT * from $table WHERE 1 ORDER BY id DESC LIMIT %d,10 ",$site) ); ?>
     <?php $count = $wpdb->get_var(" SELECT COUNT(*) from $table WHERE 1 "); ?>

     <div class="wpbody">
          <div class="wrap">
               <h2>Abonnierte Postings</h2>

               <p><b><a href="?page=cmailer/setup.php">Setup-Einstellungen</a> &raquo;</b></p>

               <p>Hier ist eine Liste aller abonnierten Postings Deines Weblogs. Du kannst entweder einzelne Abonnements von Postings löschen oder aber die Datentabelle zurücksetzen und damit alle Abos löschen.</p>

               <?php // IF SUBSCRIBERS FOUND ?>
               <?php if( $subs ) : ?>

               <?php // PAGES ?>
               <div class="tablenav">
                    <b>Seiten:</b>
                    <?php if( $count%10 == 0 ) $pages=$count/10; else $pages=floor($count/10)+1; ?>
                    <?php for($i=0;$i<$pages;$i++) : ?>
                    <a href="?page=cmailer/cmailer.php&site=<?php echo $i; ?>" style="padding:5px; font-weight:bold; <?php if( @$_GET['site'] == $i ) echo "color:#333;"; ?>"><?php echo $i+1; ?></a>
                    <?php endfor; ?>
               </div><br/>

               <table class="widefat">
                    <thead>
                         <tr>
                              <th scope="col">ID</th>
                              <th scope="col">Posting</th>
                              <th scope="col">Nickname</th>
                              <th>Status</th>
                              <th scope="col">Aktion</th>
                         </tr>
                    </thead>
                    <tbody>
                         <?php foreach( $subs as $subs ) : ?>
                         <tr class="comment">
                              <td>
                                   <?php echo $subs->post_id; ?>
                              </td>
                              <td>
                                   <a href="<?php echo $wpdb->get_var(" SELECT guid FROM $wpdb->posts WHERE ID=$subs->post_id "); ?>">
                                   <?php echo $wpdb->get_var(" SELECT post_title FROM $wpdb->posts WHERE ID=$subs->post_id "); ?>
                                   </a>
                              </td>
                              <td>
                                   <a href="mailto:<?php echo $subs->email; ?>"><?php echo $subs->name; ?></a>
                              </td>
                              <td>
                                   <?php echo $subs->status; ?>
                              </td>
                              <td>
                                   <a href="?page=cmailer/cmailer.php&cmd=unsubscribe&id=<?php echo md5($subs->id); ?>">Löschen</a>
                              </td>
                         </tr>
                         <?php endforeach; ?>
                    </tbody>
               </table>
               <div class="tablenav">
                    <form method="post">
                    <input type="hidden" name="cmd" value="unsubscribeAll">
                    <input type="submit" value="Alle Abos löschen" class="button-secondary" onlick="return confirm('Bist Du sicher, dass Du alle Abonnements löschen möchtest?');">
                    </form>
               </div>

               <br/>

               <?php else : ?><p><b>Keine Abonnements zu Postings gefunden!</b></p><?php endif; ?>

               <p>&copy; Copyright 2008 by <a href="http://www.smallit.de">smallit</a>.<br/>
               Dieses Wordpress-Weblog verwendet <b><a href="http://sit.24stunden.de/cmailer/">CommentMailer-<?php echo CM_VERSION; ?></a> &raquo;</b></p>

          </div>
     </div>

     <?php endif; ?>
     <?php } ?>