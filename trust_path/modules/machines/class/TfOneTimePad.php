<?php

/**
 * TfOneTimePad trait file.
 * 
 * @copyright   Simon Wilkinson 2018+(https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     machines
 */
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Implement encryption and decryption of messages against a one time pad.
 * 
 * This class was written to provide casual security for microcontrollers and internet of things
 * (IOT) devices transmitting data over the air, where hardware limitations make use of mainstream
 * cryptography difficult.
 * 
 * One time pads are considered too cumbersome for use in the era of public key cryptography
 * due to the problem of pad distribution. However, they do have some characteristics that may be of
 * interest to a private Internet of Things network, with a limited number of nodes. Namely, i) they
 * are ultra light weight in terms of computational and programme storage requirements, 
 * and ii) they are highly efficient in terms of bandwidth, which makes them suitable for protocols
 * that severely restrict message length and transmission frequency.
 * 
 * The remote machine needs to have enough storage to hold a good sized pad, for example a micro SD
 * card slot. You will need to load the pad onto the remote machine before you install it in the
 * field.
 * 
 * Sections of the pad used in encryption / decryption operations are deleted to prevent accidental
 * re-use. The remote machine should also implement this behaviour.
 * 
 * To keep the pad synchronised between Tuskfish and the remote device, utilise the pad from the
 * end rather than the start; this allows the current pad length on the remote machine to be used
 * as a common reference point to resynchronise the pads if a message is lost, or in the event that
 * a device is restarted.
 * 
 * Two encryption/decryption modes are offered. The first, encryptXor(), XORs plaintext against a
 * one time pad consisting of random code points from the full ASCII range (0 - 127). The second,
 * encryptText(), uses printable ASCII characters only (code points 32 - 127) and performs mod 96
 * addition. The ciphertext output is also printable ASCII, so it is more convenient if you want 
 * all aspects of the system to be human readable.
 * 
 * Both are equally effective in obscuring communications over the air. So long as i) the pad is
 * truly random, ii) the pad is never re-used and iii) the pad is not captured by busybodies the
 * encryption cannot be cracked, although other types of attack are possible. You may find one or
 * the other more convenient, depending on how you are generating random numbers and on the
 * capabilities of your remote (encrypting) devices. 
 * 
 * Generally speaking, the XOR functions are easier to generate pads for, as they use a power of 
 * two key space (128 characters), which is convenient for a hardware RNG spitting out bits.
 * The downside is that they need to hex encode the pad and ciphertext, in order to represent
 * non-printing ASCII values and to prevent the null byte being interpreted and causing problems, eg
 * because it is the string terminator in C. The hex encoding scheme used here takes 2 characters to
 * represent every ASCII value; since this doubles the message size if you are sending ASCII data
 * over a severely bandwidth limited protocol it can be an issue (although this problem goes away
 * with binary).
 * 
 * The encryptText() methods are harder to produce random pads for, since hardware RNGs are not
 * going to spit out random values in the 32-127 range without some messing around. However, using
 * the printable ASCII character set avoids the need for hexadecimal encoding so each character
 * can be encrypted with a single byte. 
 * 
 * If you seriously want your data to remain private then I suggest that you use a hardware random
 * number generator to prepare your one-time pads. I got myself a BitBabbler Black from 
 * http://www.bitbabbler but there are a reasonable number of alternative options if you Google
 * about.
 * 
 * For serious privacy, you should NOT decrypt incoming data on the server. Use Tuskfish as a dumb
 * capture point for encrypted data, export it to your local machine and decrypt it there. This
 * trait is for casual over-the-air privacy; it provides no end point security at all.
 * 
 * NOTE: You must not re-use a *one-time* pad. Doing so *completely* breaks the security.
 *
 * @copyright   Simon Wilkinson 2018+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     machines
 */
trait TfOneTimePad
{
    private $modulo = 96;
    private $ascii_offset = 32;
    
    /**
     * Encrypt a plaintext message against a one time pad.
     * 
     * The pad must consist of hexadecimal ASCII code points. Each hexadecimal value must be two
     * characters in length, so leading zeroes must be added to values in the range 0-9.
     * 
     * To decrypt the ciphertext, use decryptXor() with the same pad.
     * 
     * @param string $message Plaintext message.
     * @param string $hexadecimalPad One time pad encoded in hexadecimal ASCII code points.
     * @return string Encrypted message encoded in hexadecimal ASCII code points.
     */
    public function encryptXor(string $message, string $hexadecimalPad)
    {        
        // Convert both plaintext and hexadecimal pad to decimal ASCII codes.
        $asciiMessage = $this->convertTextToAsciiCodePoints($message);
        $asciiPad = $this->convertHexToAsciiCodePoints($hexadecimalPad);

        // Check that there is sufficient pad to encrypt the message.
        $padLength = count($asciiPad);
        $messageLength = count($asciiMessage);

        if ($padLength < $messageLength) {
            trigger_error(TFISH_ERROR_PAD_TOO_SHORT, E_USER_ERROR);
            exit;
        }
        
        // XOR the plaintext and pad, convert back to hexadecimal.
        $cipherText = '';
        
        for ($i = 0; $i < $messageLength; $i++) {
            $hexValue = dechex($asciiMessage[$i] ^ $asciiPad[$i]);
            $cipherText .= substr('0' . $hexValue, -2);
            unset($hexValue);
        }
        
        return $cipherText;
    }
    
    /**
     * Decrypts a ciphertext against a one-time pad.
     * 
     * The pad must consist of hexadecimal ASCII code points. Each hexadecimal value must be two
     * characters in length, so leading zeroes must be added to values in the range 0-9.
     * 
     * @param string $hexadecimalMessage Encrypted message encoded as hexadecimal ASCII code points.
     * @param string $hexadecimalPad One time pad encoded as hexadecimal ASCII code points.
     * @return string Plaintext.
     */
    public function decryptXor(string $hexadecimalMessage, string $hexadecimalPad)
    {
        // Convert both ciphertext and pad to decimal ASCII codes.
        $asciiMessage = $this->convertHexToAsciiCodePoints($hexadecimalMessage);
        $asciiPad = $this->convertHexToAsciiCodePoints($hexadecimalPad);
        
        // Check that there is sufficient pad to encrypt the message.
        $padLength = count($asciiPad);
        $messageLength = count($asciiMessage);
        
        if ($padLength < $messageLength) {
            trigger_error(TFISH_ERROR_PAD_TOO_SHORT, E_USER_ERROR);
            exit;
        }
        
        // XOR the plaintext and pad, convert back to characters.
        $plainText = '';
        
        for ($i = 0; $i < $messageLength; $i++) {
            $plainText .= chr($asciiMessage[$i] ^ $asciiPad[$i]);
        }
        
        return $plainText;
    }
    
    /**
     * Converts a text message into an array of ASCII code points.
     * 
     * @param string $message
     * @return array Message encoded as an array of ASCII code points.
     */
    private function convertTextToAsciiCodePoints(string $message)
    {
        $messageLength = strlen($message);
        
        $asciiMessage = array();
        
        for ($i = 0; $i < $messageLength; $i++) {
            $asciiMessage[] = ord($message[$i]);
        }
        
        return $asciiMessage;
    }
    
    /**
     * Converts a message encoded in hexadecimal ASCII code points to decimal code points.
     * 
     * @param string $hexadecimalMessage Text encoded as hexadecimal code points.
     * @return string Text encoded as decimal code points ASCII.
     */
    private function convertHexToAsciiCodePoints(string $hexadecimalMessage)
    {
        $decimalMessage = array();
        
        $messageLength = strlen($hexadecimalMessage);
        
        for ($i = 0; $i < $messageLength; $i += 2) {
            $hexValue = substr($hexadecimalMessage, $i, 2);
            $decimalMessage[] = hexdec($hexValue);
        }
        
        return $decimalMessage;
    }
    
    /**
     * Converts plain text into hex-encoded ASCII code points.
     * 
     * @param string $message
     * @return array
     */
    public function convertTextToHexCodePoints(string $message)
    {
        $messageLength = strlen($message);
        
        $hexadecimalMessage = '';
        
        for ($i = 0; $i < $messageLength; $i++) {
            $hexadecimalMessage .= dechex(ord($message[$i]));
        }
        
        return $hexadecimalMessage;
    }
    
    /**
     * Encrypts / decrypts a message (printable ASCII characters, only) against a one time pad.
     * 
     * Only works with printable ASCII characters (code range 32 - 127) and operations need to be
     * modulo 96. The one time pad must be comprised of characters randomly drawn from the same range.
     * Note that this excludes the use of multibyte character sets.
     *  
     * @param string plainText Plaintext message in printable ASCII characters.
     * @param string $pad One time pad of random printable ASCII characters in the code range 32-127.
     */
    public function encryptText(string $plainText, string $pad)
    {
        $plainText = (string) $plainText;
        $plainTextLength = strlen($plainText);
       
        $cipherText = array();

        for ($i = 0; $i < $plainTextLength; $i++) {
            
            // Convert characters to their ASCII code and shift them down into the modulo 96 range.
            $plainTextChar = ord($plainText[$i]) - $this->ascii_offset;
            $padChar = ord($pad[$i]) - $this->ascii_offset;
            
            // Add the pad to the plaintext.
            $cipherTextChar = ($plainTextChar + $padChar) % $this->modulo;
            
            // Shift values back into the ASCII code range.
            $cipherTextCharAscii = $cipherTextChar + $this->ascii_offset;
            
            // Append ciphertext.
            $cipherText[$i] = chr($cipherTextCharAscii);
        }
        
        // Destroy the used section of the one time pad to prevent accidental reuse.
        //$this->deleteUsedSectionOfPad($cleanPadCursor);

        return implode("", $cipherText);
    }
    
    /**
     * Decrypt ciphertext against a one time pad.
     * 
     * @param string $cipherText
     * @param string $pad
     * @return string
     */
    public function decryptText(string $cipherText, string $pad)
    {
        $cipherText = (string) $cipherText;
        $cipherTextLength = strlen($cipherText);
        
        $plainText = array();

        for ($i = 0; $i < $cipherTextLength; $i++) {
            
            // Convert characters to their ASCII code and shift them down into the modulo range.
            $cipherTextChar = ord($cipherText[$i]) - $this->ascii_offset;
            $padChar = ord($pad[$i]) - $this->ascii_offset;
            
            // Subtract the pad from the ciphertext.
            $paddedChar = ($cipherTextChar - $padChar + $this->modulo) % $this->modulo;
            
            // Shift values back into the ASCII code range.
            $paddedCharAscii = $paddedChar + $this->ascii_offset;
            
            // Append ciphertext.
            $cipherText[$i] = chr($paddedCharAscii);
        }
        
        // Destroy the used section of the one time pad to prevent accidental reuse.
        //$this->deleteUsedSectionOfPad($cleanPadCursor);

        return implode("", $cipherText);
    }
    
    /**
     * Reads an arbitrary length substring from a one time pad file.
     * 
     * Conventions:
     * i) One pad per machine with ID as file name.
     * ii) Pads MUST be stored in the /trust_path/pads/ directory.
     * iii) Pads are encoded as fixed-length (2 character) hexadecimal ASCII codes from 0 - 127.
     * iii) Working substrings are extracted from the back end of the pad file, which MUST be
     * truncated after each encryption/decryption operation to prevent re-use and maintain pad
     * syncronisation between devices.
     * 
     * @param int $start The starting point (offset) to read from.
     * @param int $length The number of characters to read.
     * @return string|bool A one-time pad sequence read from file, or false on failure.
     */
    public function readPad(int $start, int $length)
    {
        $path = TFISH_ONE_TIME_PAD_PATH . (int) $this->id;
        
        $cleanStart = (int) $start;
        $cleanLength = (int) $length;
        
        $pad = file_get_contents($path, false, null, $cleanStart, $cleanLength);
        
        if ($pad === false) {
            trigger_error(TFISH_ERROR_COULD_NOT_OPEN_PAD, E_USER_ERROR);
        }
        
        return $pad;
    }
    
    /**
     * Truncates a one time pad file to prevent re-use.
     * 
     * Pass in the starting point the pad was read from; the pad will be truncated from that point,
     * destroying the used section of pad (and anything after it).
     * 
     * @param int $offset The point at which the file should be truncated (a length, in bytes).
     * @return boolean True on success, false on failure.
     */
    public function truncatePad(int $offset)
    {
        $cleanOffset = (int) $offset;
        $path = TFISH_ONE_TIME_PAD_PATH . (int) $this->id;
        
        // Open pad file.
        $filePointer = fopen($path, "c");
        
        if ($filePointer === false) {
            trigger_error(TFISH_ERROR_COULD_NOT_OPEN_PAD, E_USER_ERROR);
        }
        
        // Lock pad.
        if (flock($filePointer, LOCK_EX) === false) {
            fclose($filePointer);
            trigger_error(TFISH_ERROR_COULD_NOT_LOCK_PAD, E_USER_ERROR);
        }
        
        // Truncate pad to removed used section.
        if (ftruncate($filePointer, $cleanOffset) === false) {
            trigger_error(TFISH_ERROR_COULD_NOT_TRUNCATE_PAD, E_USER_ERROR);
        }
        
        // Unlock and close pad file.
        flock($filePointer, LOCK_UN);
        fclose($filePointer);
        
        return true;
    }
    
    /**
     * Destroy entire one time pad associated with this machine.
     * 
     * An authorised remote machine may order destruction of the webserver's copy of the one time
     * pad. This is a contingency that can be called if (say) the remote machine detects tampering,
     * or some unhelpful government officials decide to interfere with your stuff.
     * 
     * TODO: Replace simple removal of file contents with active pattern scrubbing.
     * 
     * If this method is called the remote machine should also destroy its own copy of the pad.
     */
    public function burnPad()
    {
        $path = TFISH_ONE_TIME_PAD_PATH . (int) $this->id;
        
        // Truncate the contents of the file, but this does not exclude forensic data recovery.
        $this->truncatePad(0);
        
        // Unlink the file, but it will only be deleted if there are no remaining links left.
        if (unlink($path) === false) {
            trigger_error(TFISH_ERROR_BURN_PAD_FAILED, E_USER_NOTICE);
            
            return false;
        }
        
        return true;
    }
    
}
