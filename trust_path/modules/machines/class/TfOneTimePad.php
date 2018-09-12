<?php

/**
 * TfOneTimePad trait file.
 * 
 * @copyright   Simon Wilkinson 2018+(https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@tuskfish.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     machines
 */
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Implement encryption and decryption of messages against a one time pad.
 * 
 * This class was written to provide casual security for internet of things (IOT) devices
 * transmitting data over the air. I needed a way to encrypt low bandwidth LoRaWAN transmissions
 * originating from remote monitoring stations (microcontrollers) to a collection point on the
 * internet (Tuskfish).
 * 
 * One time pads are often considered too cumbersome for use in the era of public key cryptography
 * due to the problem of pad distribution. However, they have some characteristics that are of use
 * in the Internet of Things: They are ultra light weight in terms of computational requirements,
 * and they are highly efficient in terms of bandwidth, which makes them suitable for protocols
 * that severely restrict message length and transmission frequency, such as LoRaWAN.
 * 
 * The remote machine needs to have enough storage to hold a good sized pad, for example a micro SD
 * card slot will allow you to fit several gigs, which should be enough to handle years of
 * transmissions over a restricted bandwidth protocol like LoRaWAN. You will need to load the pad
 * onto the remote machine before you install it in the field.
 * 
 * Sections of the pad used in encryption / decryption operations are deleted to prevent accidental
 * re-use. The remote machine should also implement this behaviour.
 * 
 * To keep the pad synchronised between Tuskfish and the remote device, utilise the pad from the
 * end rather than the start; this allows the current pad length on the remote machine to be used
 * as a common reference point if a message is lost, or in the event that a machine is restarted.
 * 
 * Two encryption/decryption modes are offered. The first, encryptXor(), XORs plaintext against a
 * one time pad consisting of random code points from the full ASCII range (0 - 127). The second,
 * encryptText(), uses printable ASCII characters only (code points 32 - 127) and performs mod 96
 * addition. The ciphertext output is also printable ASCII, so it is more convenient if you want 
 * all aspects of the system to be human readable. You may find one or the other more convenient, depending on how you are generating random numbers
 * and on the capabilities of your remote (encrypting) devices.
 * 
 * Both are equally effective in hardening communications over the air. So long as i) the pad is
 * truly random, ii) the pad is never re-used and iii) the pad is not captured by busybodies the
 * encryption cannot be cracked. 
 * 
 * If you seriously want your data to remain private then I suggest that you use a hardware random
 * number generator to prepare your one-time pads. Your computer cannot generate truly random
 * numbers; it can only provide you with psuedo-random numbers, which are not bulletproof.
 * 
 * WARNING: You must never re-use a one-time pad. Doing so *completely* breaks the security.
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
     * i) One pad per machine with machine ID as file name.
     * ii) Pads are stored in the /trust_path/uploads/pad/ directory.
     * iii) Working substrings are read from the back end of the file.
     * 
     * @param int $start The starting point to read from.
     * @param int $length The number of characters to read.
     * @return string A one-time pad sequence read from file.
     */
    private function readPad(int $start, int $length)
    {
        $pad = '';
        
        // Read the pad from file.
        
        /** For testing only **/
        $pad = '3S&esW}TQ=)DkfJXv{Q?{|6&[1quyj#cFCHP6%>!zOwk>:C1c[$rM&^2]!0/KuB';
        $pad .= '-(_Rtu_*XmSKJ2IQ?Ea8!T?^"5$^Wrk5g2b(5Y\'ndp}7=P"k!^kks}]F)JT5Z[P';
        $pad .= '|Z]4d08v3>I9<)_$?]]DVQ)/,=Bl=!.l39;KGiqf1\'%\'phfr+Q`=;.c~FWDjEFt';
        $pad .= '';
        $pad .= '';
        
        /** End for testing only **/
        
        return $pad();
    }
    
    /**
     * Check that the one time pad exists and is long enough to encrypt/decrypt the message.
     * 
     * If there isn't enough pad left, delete the message, log an error and hard exit.
     * 
     * @param string $plainText
     * @return bool True on success, false on failure.
     */
    private function checkPadLengthOk(string $message, string $oneTimePad)
    {
        // Get length of message.
        // Get length of pad.
    }
    
    /**
     * Truncates the one time pad from the starting point used in the last encrypt/decrypt operation.
     * 
     * One time pad characters used in encryption/decryption operations MUST BE DELETED to ensure
     * they cannot be re-used. The pad will be truncated (all content discarded) from the starting
     * point of the last message operation.
     * 
     * @param int $padCursor The starting position the pad was read from.
     */
    private function deleteUsedSectionOfPad(int $padCursor)
    {
        if (!$this->validator->isInt($padCursor, 0)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
        
        // Truncate the pad from $padCursor onwards.
    }
    
    /**
     * Destroy entire one time pad.
     * 
     * An authorised remote machine may order destruction of the webserver's copy of the one time
     * pad. This is a contingency that can be called if (say) the remote machine detects tampering.
     * 
     * If this method is called the remote machine should also destroy its own copy of the pad.
     */
    private function burnPad()
    {
        // Destroy pad completely.
    }
    
}
