<?php
namespace App\Config;

use Exception;

class Crypt {

    private $data;
    private $key;
    private $module;
    private $complexTypes = false;
    const HMAC_ALGORITHM = 'sha1';
    const MCRYPT_MODULE = 'rijndael-192';
    const MCRYPT_MOD = 'cfb';
    const MINIMUM_KEY_LENGTH = 8;

    function __construct() {
        $this->checkEnvironment();
        $this->setModule(mcrypt_module_open(self::MCRYPT_MODULE, '', self::MCRYPT_MOD, ''));
    }


    private function checkEnvironment() {
        if ((!extension_loaded('mcrypt')) || (!function_exists('mcrypt_module_open'))) {
            throw new Exception('The PHP mcrypt extension must be installed for encryption', 1);
        }
        if (!in_array(self::MCRYPT_MODULE, mcrypt_list_algorithms())) {
            throw new Exception("The cipher used self::MCRYPT_MODULE does not appear to be supported by the installed version of libmcrypt", 1);
        }
    }

    /**
     * Sets the data for encryption or decryption
     *
     * @param mixed $data
     * @return void
     */
    public function setData($data) {
        $this->data = $data;
    }

    /**
     * Sets the secret key for encryption or decryption, at least 8 character long
     *
     * @param string $key
     * @return void
     * @throws Exception
     */
    public function setKey($key) {
        if (strlen($key) < self::MINIMUM_KEY_LENGTH) {
            $message = sprintf('The secret key must be a minimum %s character long', self::MINIMUM_KEY_LENGTH);
            throw new Exception($message, 1);
        }
        $this->key = $key;
    }

    /**
     * Sets the mcrypt module
     *
     * @param resource $module
     * @return void
     */
    private function setModule($module) {
        $this->module = $module;
    }

    /**
     * Sets using complex data types like arrays and objects for serialization
     *
     * @param bool $complexTypes
     * @return void
     * @author Osman Üngür
     */
    public function setComplexTypes($complexTypes) {
        $this->complexTypes = $complexTypes;
    }

    /**
     * Returns the encrypted or decrypted data
     *
     * @return mixed
     */
    private function getData() {
        return $this->data;
    }

    /**
     * Returns the secret key for encryption
     *
     * @return string
     */
    private function getKey() {
        return $this->key;
    }

    /**
     * Returns the mcrypt module resource
     *
     * @return resource
     * @author Osman Üngür
     */
    private function getModule() {
        return $this->module;
    }

    /**
     * Returns true if using complex data types like arrays and objects declared
     *
     * @return bool
     */
    private function getComplexTypes() {
        return $this->complexTypes;
    }

    /**
     * Encrypts the given data using symmetric-key encryption
     *
     * @return string
     */
    public function encrypt() {
        mt_srand();
        $init_vector = mcrypt_create_iv(mcrypt_enc_get_iv_size($this->getModule()), MCRYPT_RAND);
        $key = substr(sha1($this->getKey()), 0, mcrypt_enc_get_key_size($this->getModule()));
        mcrypt_generic_init($this->getModule(), $key, $init_vector);
        if ($this->getComplexTypes()) {
            $this->setData(serialize($this->getData()));
        }
        $cipher = mcrypt_generic($this->getModule(), $this->getData());
        $hmac = hash_hmac(self::HMAC_ALGORITHM, $init_vector . $cipher, $this->getKey());
        $encoded_init_vector = base64_encode($init_vector);
        $encoded_cipher = base64_encode($cipher);
        return  $encoded_init_vector . $encoded_cipher . $hmac;
    }

    /**
     * Decrypts encrypted cipher using symmetric-key encryption
     * @return mixed
     * @throws Exception
     */
    public function decrypt() {
        $elements = explode("",$this->getData());
        if (count($elements) != 4) {
            $message = sprintf('The given data does not appear to be encrypted with %s', __CLASS__);
            throw new \Exception($message, 1);
        }
        $init_vector = base64_decode($elements[1]);
        $cipher = base64_decode($elements[2]);
        $given_hmac = $elements[3];
        $hmac = hash_hmac(self::HMAC_ALGORITHM, $init_vector . $cipher, $this->getKey());
        if ($given_hmac != $hmac) {
            throw new \Exception('The given data appears tampered or corrupted', 1);
        }
        $key = substr(sha1($this->getKey()), 0, mcrypt_enc_get_key_size($this->getModule()));
        mcrypt_generic_init($this->getModule(), $key, $init_vector);
        $result = mdecrypt_generic($this->getModule(), $cipher);
        if ($this->getComplexTypes()) {
            return unserialize($result);
        }
        return $result;
    }

    public function __destruct() {
        @mcrypt_generic_deinit($this->getModule());
        mcrypt_module_close($this->getModule());
    }

}

