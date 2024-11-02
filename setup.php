<?php

$opt = get_option("CM_OPTIONS");

if( ! $opt['from'] ) :
     $opt['from'] = get_option("admin_email");
     update_option("CM_OPTIONS",$opt);
endif;

if( @$_POST['cmd'] == 'saveallchanges' ) :
     if( ! $opt['from'] = @$_POST['from'] ) $opt['from'] = get_option("admin_email");
     if( ! $opt['extra'] = @$_POST['extra'] ) $opt['extra'] = '';
     update_option("CM_OPTIONS",$opt);
endif;

?>

<div class="wpbody">
     <div class="wrap">

     <h2>CommentMailer-Einstellungen</h2>

     <p>Hier können Provider-spezifische Einstellungen für den Mailversand vorgenommen werden.</p>

     <div style="background-color:#2782AF; padding:10px; -moz-border-radius:0.5em; border:1px solid #39BDFF; color:white;">

          <form method="post">
          <input type="hidden" name="cmd" value="saveallchanges">
          <p><b>Von:</b><br/>
          <input type="text" name="from" value="<?php echo @$opt['from']; ?>" style="width:250px;" class="button-secondary"><br/>
          <em>(Absnder der Benachrichtigung wie noreply@yourhost.com)</em>
          </p>

          <p><b>Extras:</b><br/>
          <input type="text" name="extra" value="<?php echo @$opt['extra']; ?>" style="width:250px;" class="button-secondary"><br/>
          <em>(Einige Provider benötigen eine Verifizierung der E-Mail bevor die Mail über den Mailserver versendet werden kann. Verwende dazu den Parameter -f gültige@email.de)</em>
          </p>

          <p><input type="submit" value="Einstellungen speichern" class="button-secondary"></p>
          </form>

     </div>

     <p>&copy; Copyright 2008 by <a href="http://www.smallit.de">smallit</a><br/>
     Dieses Wordpress-Weblog verwendet <b><a href="http://sit.24stunden.de/cmailer/">CommentMailer-<?php echo CM_VERSION; ?></a> &raquo;</b></p>

     </div>
</div>