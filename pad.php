<?php

/**
 * Test script for one time pad.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     core
 */
// Enable strict type declaration.
declare(strict_types=1);

// 1. Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";

// 2. Main Tuskfish header. This file bootstraps Tuskfish.
require_once TFISH_PATH . "tfHeader.php";

// 3. Content header sets module-specific paths and makes TfContentHandlerFactory available.
require_once TFISH_MODULE_PATH . "machines/tfMachinesHeader.php";

////////// CONFIGURATION //////////
$tfTemplate->setTheme('default');
////////// END CONFIGURATION //////////

define("ASCII_OFFSET", 32);
define("MODULO", 96);
$plainText = "ABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890";
$pad = "9jfMkjzFzkJNZqeGAdR3iuOZzABncf9dDXn0QJuBhaXda0Csu3fNZuHXkAR9Ef6FP>kX}zlB|5:T9BM2uB]xszyb(&1xb9jacaOk@F,I+i++*?pJ.0UerW:dZ;HW";

// Get a machine.
$machine = $machineFactory->getMachine();

// Convert pad to hex.
$hexidecimalPad = $machine->convertTextToHexCodePoints($pad);

echo "Test message...<br />";
echo $plainText . '<br /><br />';

echo 'Encrypting...<br />';
//$cipherText = encryptMessage($plainText, $pad);
//$cipherText = $machine->encryptText($plainText, $pad);
$cipherText = $machine->encryptXor($plainText, $hexidecimalPad);
echo $cipherText . '<br /><br />';

echo 'Decrypting...<br />';
//$plainText = decryptMessage($cipherText, $pad);
//$plaintext = $machine->decryptText($cipherText, $pad);
$plainText = $machine->decryptXor($cipherText, $hexidecimalPad);

echo $plainText . '<br /><br />';

/**
 * Override page template and metadata here (otherwise default site metadata will display).
 */
// $tfMetadata->setTitle('');
// $tfMetadata->setDescription('');
// $tfMetadata->setAuthor('');
// $tfMetadata->setCopyright('');
// $tfMetadata->setGenerator('');
// $tfMetadata->setSeo('');
// $tfMetadata->setRobots('');

// Include page template and flush buffer
require_once TFISH_PATH . "tfFooter.php";
