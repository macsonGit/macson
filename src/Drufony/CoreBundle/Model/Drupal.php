<?php
/**
 * Implements a set of Drupal methods. This is a former static class which contains some
 * helpers which can be used to define several functionalities around the project.
 *
 * It's deprecated and will be fully substituted with new methods in Utils, ContentUtils or
 * CommerceUtils classes.
 */

namespace Drufony\CoreBundle\Model;

// Class constants
define('DRUPAL_MIN_HASH_COUNT', 7);
define('DRUPAL_MAX_HASH_COUNT', 30);
define('DRUPAL_HASH_LENGTH', 55);
define('DRUPAL_HASH_COUNT', 15);
define('LANGUAGE_NONE', 'und');
define('DRUPAL_INT_TO_BASE64', './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');
define('DRUPAL_ALLOWABLE_CHARACTERS', 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789');

/**
 * Drupal Static class. Defines several helpers from Drupal CMS which can be used to define some
 * functionalities.
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class Drupal
{
    /**
     * Logs in a user with the local Drupal backend.
     *
     * @param array $userData Associative array with the user credentials.
     */
    static public function login($userData,$password) {
        /* Check account status: blocked users cannot login. */
        if (self::active === 0) {
            return false;
        }

        /* Password matching. */
        if (self::user_check_password($password, $userData['$passwordHash']) === true) {
            /* Grant login: set cookie */
            Session::setSessionCookie($userData['uid']);
            return true;
        }
        return false;
    }

    /*
     * PRIVATE DRUPAL FUNCTIONS
    */

    static public function user_check_password($password, $stored_hash)
    {
        $type = substr($stored_hash, 0, 3);
        switch ($type) {
            case '$S$':
                // A normal Drupal 7 password using sha512.
                $hash = self::password_crypt('sha512', $password, $stored_hash);
                break;

            case '$H$':
                // phpBB3 uses "$H$" for the same thing as "$P$".
            case '$P$':
                // A phpass password generated using md5.  This is an
                // imported password or from an earlier Drupal version.
                $hash = self::password_crypt('md5', $password, $stored_hash);
                break;

            default:
                return FALSE;
        }

        return ($hash && $stored_hash == $hash);
    }

    static public function password_crypt($algo, $password, $setting) {
        // The first 12 characters of an existing hash are its setting string.
        $setting = substr($setting, 0, 12);

        if ($setting[0] != '$' || $setting[2] != '$') {
            return FALSE;
        }
        $count_log2 = self::password_get_count_log2($setting);
        // Hashes may be imported from elsewhere, so we allow != DRUPAL_HASH_COUNT
        if ($count_log2 < DRUPAL_MIN_HASH_COUNT || $count_log2 > DRUPAL_MAX_HASH_COUNT) {
            return FALSE;
        }

        $salt = substr($setting, 4, 8);
        // Hashes must have an 8 character salt.
        if (strlen($salt) != 8) {
            return FALSE;
        }

        // Convert the base 2 logarithm into an integer.
        $count = 1 << $count_log2;

        // We rely on the hash() function being available in PHP 5.2+.
        $hash = hash($algo, $salt . $password, TRUE);

        do {
            $hash = hash($algo, $hash . $password, TRUE);
        } while (--$count);

        $len = strlen($hash);
        $output = $setting . self::password_base64_encode($hash, $len);
        // _password_base64_encode() of a 16 byte MD5 will always be 22 characters.
        // _password_base64_encode() of a 64 byte sha512 will always be 86 characters.
        $expected = 12 + ceil((8 * $len) / 6);
        return (strlen($output) == $expected) ? substr($output, 0, DRUPAL_HASH_LENGTH) : FALSE;
    }

    static public function password_get_count_log2($setting) {
        $itoa64 = DRUPAL_INT_TO_BASE64;
        return strpos($itoa64, $setting[3]);
    }

    static public function password_base64_encode($input, $count) {
        $output = '';
        $i = 0;
        $itoa64 = DRUPAL_INT_TO_BASE64;
        do {
            $value = ord($input[$i++]);
            $output .= $itoa64[$value & 0x3f];
            if ($i < $count) {
                $value |= ord($input[$i]) << 8;
            }
            $output .= $itoa64[($value >> 6) & 0x3f];
            if ($i++ >= $count) {
                break;
            }
            if ($i < $count) {
                $value |= ord($input[$i]) << 16;
            }
            $output .= $itoa64[($value >> 12) & 0x3f];
            if ($i++ >= $count) {
                break;
            }
            $output .= $itoa64[($value >> 18) & 0x3f];
        } while ($i < $count);

        return $output;
    }

    static public function user_password($length = 10) {
        // This variable contains the list of allowable characters for the
        // password. Note that the number 0 and the letter 'O' have been
        // removed to avoid confusion between the two. The same is true
        // of 'I', 1, and 'l'.
        $allowable_characters = DRUPAL_ALLOWABLE_CHARACTERS;

        // Zero-based count of characters in the allowable list:
        $len = strlen($allowable_characters) - 1;

        // Declare the password as a blank string.
        $pass = '';

        // Loop the number of times specified by $length.
        for ($i = 0; $i < $length; $i++) {
            do {
                // Find a secure random number within the range needed.
                $index = ord(self::random_bytes(1));
            } while ($index > $len);

            // Each iteration, pick a random character from the
            // allowable string and append it to the password:
            $pass .= $allowable_characters[$index];
        }

        return $pass;
    }

    static public function user_hash_password($password, $count_log2 = 0)
    {
        if (empty($count_log2)) {
            // Use the standard iteration count.
            $count_log2 = DRUPAL_HASH_COUNT;
        }
        return self::password_crypt('sha512', $password, self::password_generate_salt($count_log2));
    }

    static public function password_generate_salt($count_log2) {
        $output = '$S$';
        // Ensure that $count_log2 is within set bounds.
        $count_log2 = self::password_enforce_log2_boundaries($count_log2);
        // We encode the final log2 iteration count in base 64.
        $itoa64 = DRUPAL_INT_TO_BASE64;
        $output .= $itoa64[$count_log2];
        // 6 bytes is the standard salt for a portable phpass hash.
        $output .= self::password_base64_encode(self::random_bytes(6), 6);
        return $output;
    }

    static public function password_enforce_log2_boundaries($count_log2)
    {
        if ($count_log2 < DRUPAL_MIN_HASH_COUNT) {
            return DRUPAL_MIN_HASH_COUNT;
        }
        elseif ($count_log2 > DRUPAL_MAX_HASH_COUNT) {
            return DRUPAL_MAX_HASH_COUNT;
        }

        return (int) $count_log2;
    }

    /**
     * Private helper for construct session_id.
     */
    static public function hash_base64($data)
    {
        $hash = base64_encode(hash('sha256', $data, TRUE));
        // Modify the hash so it's safe to use in URLs.
        return strtr($hash, array('+' => '-', '/' => '-', '=' => ''));
    }

    /**
     * Private helper for construct session_id.
     */
    static public function random_bytes($count)
    {
        static $random_state, $bytes, $php_compatible;

        if (!isset($random_state)) {
            $random_state = print_r($_SERVER, TRUE);
            if (function_exists('getmypid')) {
                $random_state .= getmypid();
            }
            $bytes = '';
        }

        if (strlen($bytes) < $count) {
            if (!isset($php_compatible)) {
                $php_compatible = version_compare(PHP_VERSION, '5.3.4', '>=');
            }

            if ($fh = @fopen('/dev/urandom', 'rb')) {
                $bytes .= fread($fh, max(4096, $count));
                fclose($fh);
            }
            else if ($php_compatible && function_exists('openssl_random_pseudo_bytes')) {
                $bytes .= openssl_random_pseudo_bytes($count - strlen($bytes));
            }

            while (strlen($bytes) < $count) {
                $random_state = hash('sha256', microtime() . mt_rand() . $random_state);
                $bytes .= hash('sha256', mt_rand() . $random_state, TRUE);
            }
        }
        $output = substr($bytes, 0, $count);
        $bytes = substr($bytes, $count);
        return $output;
    }

    static public function strip_dangerous_protocols($uri) {
        static $allowed_protocols;

        if (!isset($allowed_protocols)) {
            $allowed_protocols = array_flip(self::variable_get('filter_allowed_protocols', array('ftp', 'http', 'https', 'irc', 'mailto', 'news', 'nntp', 'rtsp', 'sftp', 'ssh', 'tel', 'telnet', 'webcal')));
        }

        // Iteratively remove any invalid protocol found.
        do {
            $before = $uri;
            $colonpos = strpos($uri, ':');
            if ($colonpos > 0) {
                // We found a colon, possibly a protocol. Verify.
                $protocol = substr($uri, 0, $colonpos);
                // If a colon is preceded by a slash, question mark or hash, it cannot
                // possibly be part of the URL scheme. This must be a relative URL, which
                // inherits the (safe) protocol of the base document.
                if (preg_match('![/?#]!', $protocol)) {
                    break;
                }
                // Check if this is a disallowed protocol. Per RFC2616, section 3.2.3
                // (URI Comparison) scheme comparison must be case-insensitive.
                if (!isset($allowed_protocols[strtolower($protocol)])) {
                    $uri = substr($uri, $colonpos + 1);
                }
            }
        } while ($before != $uri);

        return $uri;
    }

    static private function variable_get($name, $default = NULL) {
        global $conf;

        return isset($conf[$name]) ? $conf[$name] : $default;
    }

    static public function validate_utf8($text) {
        if (strlen($text) == 0) {
            return TRUE;
        }

        return (preg_match('/^./us', $text) == 1);
    }

    static public function check_plain($text) {
        return  htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    static public function filter_xss($string,$allowed_tags=array('a', 'em', 'strong', 'cite',
            'blockquote', 'code', 'ul', 'ol', 'li', 'dl', 'dt', 'dd'))
    {
        // Only operate on valid UTF-8 strings. This is necessary to prevent cross
        // site scripting issues on Internet Explorer 6.
        if (!self::validate_utf8($string)) {
            return '';
        }
        // Store the text format.
        self::_filter_xss_split($allowed_tags, TRUE);
        // Remove NULL characters (ignored by some browsers).
        $string = str_replace(chr(0), '', $string);
        // Remove Netscape 4 JS entities.
        $string = preg_replace('%&\s*\{[^}]*(\}\s*;?|$)%', '', $string);

        // Defuse all HTML entities.
        $string = str_replace('&', '&amp;', $string);
        // Change back only well-formed entities in our whitelist:
        // Decimal numeric entities.
        $string = preg_replace('/&amp;#([0-9]+;)/', '&#\1', $string);
        // Hexadecimal numeric entities.
        $string = preg_replace('/&amp;#[Xx]0*((?:[0-9A-Fa-f]{2})+;)/', '&#x\1', $string);
        // Named entities.
        $string = preg_replace('/&amp;([A-Za-z][A-Za-z0-9]*;)/', '&\1', $string);

        return preg_replace_callback('%
                (
                <(?=[^a-zA-Z!/])  # a lone <
                |                 # or
                <!--.*?-->        # a comment
                |                 # or
                <[^>]*(>|$)       # a string that starts with a <, up until the > or the end of the string
                |                 # or
                >                 # just a >
        )%x', 'self::_filter_xss_split', $string);
    }

    static private function _filter_xss_split($m, $store = FALSE) {
        static $allowed_html;

        if ($store) {
            $allowed_html = array_flip($m);
            return;
        }

        $string = $m[1];

        if (substr($string, 0, 1) != '<') {
            // We matched a lone ">" character.
            return '&gt;';
        }
        elseif (strlen($string) == 1) {
            // We matched a lone "<" character.
            return '&lt;';
        }

        if (!preg_match('%^<\s*(/\s*)?([a-zA-Z0-9]+)([^>]*)>?|(<!--.*?-->)$%', $string, $matches)) {
            // Seriously malformed.
            return '';
        }

        $slash = trim($matches[1]);
        $elem = &$matches[2];
        $attrlist = &$matches[3];
        $comment = &$matches[4];

        if ($comment) {
            $elem = '!--';
        }

        if (!isset($allowed_html[strtolower($elem)])) {
            // Disallowed HTML element.
            return '';
        }

        if ($comment) {
            return $comment;
        }

        if ($slash != '') {
            return "</$elem>";
        }

        // Is there a closing XHTML slash at the end of the attributes?
        $attrlist = preg_replace('%(\s?)/\s*$%', '\1', $attrlist, -1, $count);
        $xhtml_slash = $count ? ' /' : '';

        // Clean up attributes.
        $attr2 = implode(' ', self::_filter_xss_attributes($attrlist));
        $attr2 = preg_replace('/[<>]/', '', $attr2);
        $attr2 = strlen($attr2) ? ' ' . $attr2 : '';

        return "<$elem$attr2$xhtml_slash>";
    }

    static private function _filter_xss_bad_protocol($string, $decode = TRUE) {
        // Get the plain text representation of the attribute value (i.e. its meaning).
        // @todo Remove the $decode parameter in Drupal 8, and always assume an HTML
        //   string that needs decoding.
        if ($decode) {

            $string = html_entity_decode($string);
        }
        return self::check_plain(self::strip_dangerous_protocols($string));
    }


    static private function _filter_xss_attributes($attr) {
        $attrarr = array();
        $mode = 0;
        $attrname = '';

        while (strlen($attr) != 0) {
            // Was the last operation successful?
            $working = 0;

            switch ($mode) {
                case 0:
                    // Attribute name, href for instance.
                    if (preg_match('/^([-a-zA-Z]+)/', $attr, $match)) {
                        $attrname = strtolower($match[1]);
                        $skip = ($attrname == 'style' || substr($attrname, 0, 2) == 'on');
                        $working = $mode = 1;
                        $attr = preg_replace('/^[-a-zA-Z]+/', '', $attr);
                    }
                    break;

                case 1:
                    // Equals sign or valueless ("selected").
                    if (preg_match('/^\s*=\s*/', $attr)) {
                        $working = 1;
                        $mode = 2;
                        $attr = preg_replace('/^\s*=\s*/', '', $attr);
                        break;
                    }

                    if (preg_match('/^\s+/', $attr)) {
                        $working = 1;
                        $mode = 0;
                        if (!$skip) {
                            $attrarr[] = $attrname;
                        }
                        $attr = preg_replace('/^\s+/', '', $attr);
                    }
                    break;

                case 2:
                    // Attribute value, a URL after href= for instance.
                    if (preg_match('/^"([^"]*)"(\s+|$)/', $attr, $match)) {
                        $thisval = self::_filter_xss_bad_protocol($match[1]);

                        if (!$skip) {
                            $attrarr[] = "$attrname=\"$thisval\"";
                        }
                        $working = 1;
                        $mode = 0;
                        $attr = preg_replace('/^"[^"]*"(\s+|$)/', '', $attr);
                        break;
                    }

                    if (preg_match("/^'([^']*)'(\s+|$)/", $attr, $match)) {
                        $thisval = self::_filter_xss_bad_protocol($match[1]);

                        if (!$skip) {
                            $attrarr[] = "$attrname='$thisval'";
                        }
                        $working = 1;
                        $mode = 0;
                        $attr = preg_replace("/^'[^']*'(\s+|$)/", '', $attr);
                        break;
                    }

                    if (preg_match("%^([^\s\"']+)(\s+|$)%", $attr, $match)) {
                        $thisval = self::_filter_xss_bad_protocol($match[1]);

                        if (!$skip) {
                            $attrarr[] = "$attrname=\"$thisval\"";
                        }
                        $working = 1;
                        $mode = 0;
                        $attr = preg_replace("%^[^\s\"']+(\s+|$)%", '', $attr);
                    }
                    break;
            }

            if ($working == 0) {
                // Not well formed; remove and try again.
                $attr = preg_replace('/
                        ^
                        (
                        "[^"]*("|$)     # - a string that starts with a double quote, up until the next double quote or the end of the string
                        |               # or
                        \'[^\']*(\'|$)| # - a string that starts with a quote, up until the next quote or the end of the string
                        |               # or
                        \S              # - a non-whitespace character
                )*              # any number of the above three
                        \s*             # any number of whitespaces
                        /x', '', $attr);
                $mode = 0;
            }
        }

        // The attribute list ends with a valueless attribute like "selected".
        if ($mode == 1 && !$skip) {
            $attrarr[] = $attrname;
        }
        return $attrarr;
    }

    /**
     * @see https://api.drupal.org/api/drupal/includes%21file.inc/function/file_munge_filename/7
     */
    static public function file_munge_filename($filename, $extensions)
    {
      $original = $filename;
      // Remove any null bytes. See http://php.net/manual/security.filesystem.nullbytes.php
      $filename = str_replace(chr(0), '', $filename);

      $whitelist = array_unique(explode(' ', trim($extensions)));

      // Split the filename up by periods. The first part becomes the basename
      // the last part the final extension.
      $filename_parts = explode('.', $filename);
      $new_filename = array_shift($filename_parts); // Remove file basename.
      $final_extension = array_pop($filename_parts); // Remove final extension.

      // Loop through the middle parts of the name and add an underscore to the
      // end of each section that could be a file extension but isn't in the list
      // of allowed extensions.
      foreach ($filename_parts as $filename_part) {
        $new_filename .= '.' . $filename_part;
        if (!in_array($filename_part, $whitelist) && preg_match("/^[a-zA-Z]{2,5}\d?$/", $filename_part)) {
          $new_filename .= '_';
        }
      }
      $filename = $new_filename . '.' . $final_extension;
      return $filename;
    }

    public static function int2vancode($i = 0) {
      $num = base_convert((int) $i, 10, 36);
      $length = strlen($num);
      return chr($length + ord('0') - 1) . $num;
    }

    public static function vancode2int($c = '00') {
        return base_convert(substr($c, 1), 36, 10);
    }
}
