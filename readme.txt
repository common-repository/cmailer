=== CommentMailer24 ===
Contributors: Sebastian Schwaner
Donate link: http://quietschbunt.wordpress.com/commentmailer
Tags: comments
Requires at least: 2.1.0
Tested up to: 2.6.2
Stable tag: 0.1.5.2

CommentMailer benachrichtigt LeserInnen des Weblogs bei neuen Kommentaren zu abonnierten Postings.

== Description ==

CommentMailer benachrichtigt LeserInnen des Weblogs bei neuen Kommentaren zu abonnierten Postings. Die Abonnentenverwaltung erfolgt über eine seperat angelegte Datentabelle. Dabei verfügt es über ein Double-Opt-In-Verfahren, dass eine weitere Bestätigung eines Abonnenten einfordert, bevor dieser Benachrichtigungen zum gewünschten Kommentar erhält. Abonnenten, die bereits eine für den Versand einer Benachrichtigung verfizierte E-Mailadresse im System haben, bekommen keine weitere Verifizierungen mehr zugesandt.

== Installation ==

1. Download des ZIP-Archivs.
2. Entpacken des Archivs.
3. Den gesamten Ordner in das Plugin-Verzeichnis der Wordpress-Installation hochladen (wp-content/plugins)
4. Einloggen als Admin und Aktivieren des Plugins.
5. Klicke auf Kommentare und wähle "CommentMailer" aus dem Untermenu.
6. Klicke auf den Button "Anlegen/Update", um eine neue Datentabelle anzulegen.
7. Überprüfe die Einstellungen und gebe ggf. gesonderte Parameter für die PHP-Funktion mail() an (z.B. -f )
8. Bearbeite das Kommentar-Template (comments.php) Deines Themes (mit Notepad or GEdit)
9. Älteren PHP-Code bitte aus dem Template entfernen
10. Füge die folgenden Zeilen PHP-Code direkt hinter die Textarea des Kommentarbereiches ein (hinter </textarea>).

     &lt;?php // CommentMailer ?&gt;
     &lt;?php if( function_exists("setCommentMailer") ) setCommentMailer( $_GET ); ?&gt;

11. Lade das aktualisierte Themefile zurück auf den Server
12. Logge Dich aus und überprüfe Dein Weblog.
13. Gut gemacht!

== Frequently Asked Questions ==

== Screenshots ==

http://quietschbunt.files.wordpress.com/2008/10/cmailer.png

