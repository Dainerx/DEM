<?php

namespace DEM;

class IO
{
    const VERSION = "0.0.1";
    //output constants
    const OUTPUT_RUNNING = "Running DEM " . self::VERSION . " from " . __DIR__ . " ...";
    const OUTPUT_PROJECT_CONFIG_START = "Generating project metadata...";
    const OUTPUT_PROJECT_CONFIG_FAILED = "Project metadata generation has failed:";
    const OUTPUT_PROJECT_CONFIG_END = "Project metadata generation has been successfully completed.\nMetadata parsed in ";
    const OUTPUT_CLASS_CONFIG_START = "Started generating metadata for ";
    const OUTPUT_CLASS_CONFIG_FAILED = "Generating metadata has failed for ";
    const OUTPUT_OK = "OK.";
    const OUTPUT_NEWLINE = " \n";
    //output type constants
    const SUCCESS = "sucess";
    const INFO = "info";
    const ERROR = "error";
    //colors
    const BLACK = "40";
    const GREEN = "0;32";
    const RED = "0;31";
    const WHITE = "0;37";

    public static function println($message = "", $type = self::INFO)
    {
        $color = null;
        if ($type == self::SUCCESS)
            $color = self::GREEN;
        else if ($type == self::INFO)
            $color = self::WHITE;
        else
            $color = self::RED;

        $output = "\033[" . $color . "m" . $message . "\033[0m";
        echo $output . self::OUTPUT_NEWLINE;
    }

    public static function isFile($file)
    {
        return file_exists($file);
    }

    public static function readFile($file)
    {
        return (self::isFile($file)) ? file_get_contents($file) : "";
    }

    public static function writeFile($file, $content, $successMessage = "sucess", $failedMessage = "failed")
    {
        try {
            $handle = fopen($file, "w");
            fwrite($handle, $content);
            self::println($successMessage, self::SUCCESS);
        } catch (Exception $e) {
            self::println($failedMessage, self::ERROR);
            self::println($e->getMessage(), self::ERROR);
        }
    }

    public static function removeFile($file, $successMessage = "sucess", $failedMessage = "failed")
    {
        try {
            unlink($file);
            self::println($successMessage, self::SUCCESS);
        } catch (Exception $e) {
            self::println($failedMessage, self::ERROR);
            self::println($e->getMessage(), self::ERROR);
        }
    }
}
