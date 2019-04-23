<?php

namespace EdisonLabs\MergeYaml;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Main class for merge-yaml.
 */
class MergeYaml
{

  /**
   * The directory where the merged files will be placed.
   *
   * @var string
   */
    public $outputDir;

    /**
     * The file name patterns to scan for.
     *
     * @var array
     */
    public $fileNamePatterns;

    /**
     * The paths to scan recursively for yaml files.
     *
     * @var array
     */
    public $sourcePaths;

    /**
     * MergeYaml constructor.
     *
     * @param array  $files
     *   The files patterns.
     * @param array  $locations
     *   The source paths.
     * @param string $outputDir
     *   Path where the merged files will be saved.
     */
    public function __construct(array $files, array $locations, $outputDir)
    {
        $this->fileNamePatterns = $files;

        $this->sourcePaths = array();
        foreach ($locations as $path) {
            $this->sourcePaths[] = realpath($path);
        }

        $this->outputDir = $outputDir;
    }

    /**
     * Creates the output directory if doesn't exist.
     */
    public function prepareOutputDir()
    {
        // Check if the output directory exists and try to create it if it doesn't.
        if (!is_dir($this->outputDir) && !mkdir($this->outputDir, 0700)) {
            throw new \RuntimeException(sprintf('Output directory does not exist and it was not able to be created: %s.', $this->outputDir));
        }
    }

    /**
     * Returns the content of the merged yaml files.
     *
     * @param array $filePaths
     *   An array containing the file paths.
     *
     * @return string
     *   The yaml content.
     */
    public function getMergedYmlContent(array $filePaths)
    {
        $mergedValue = array();

        foreach ($filePaths as $filePath) {
            try {
                $fileContent = file_get_contents($filePath);
                $parsedFile = Yaml::parse($fileContent);
            } catch (ParseException $exception) {
                throw new \RuntimeException(sprintf("Unable to parse the file %s as YAML: %s", $filePath, $exception->getMessage()));
            }

            if (!is_array($parsedFile)) {
                $parsedFile = array();
            }

            $mergedValue = array_merge_recursive($mergedValue, $parsedFile);
        }

        return Yaml::dump($mergedValue, PHP_INT_MAX, 2);
    }

    /**
     * Create the merge files.
     *
     * @return array
     *   Returns the processed files.
     */
    public function createMergeFiles()
    {
        // Check if the output directory exists and try to create it if it doesn't.
        $this->prepareOutputDir();

        $ymlFilesPaths = $this->getYamlFiles();
        if (empty($ymlFilesPaths)) {
            // No valid Yaml files were found.
            return array();
        }

        foreach ($ymlFilesPaths as $fileName => $filePaths) {
            $outputFileName = $fileName.'.merge.yml';
            $yaml = $this->getMergedYmlContent($filePaths);

            // Save file.
            file_put_contents($this->outputDir.'/'.$outputFileName, $yaml);
        }

        return $ymlFilesPaths;
    }

    /**
     * Gets all yaml files matching fileNamePatterns inside the sourcePaths.
     *
     * @return array
     *   The absolute paths to the valid yaml files.
     */
    public function getYamlFiles()
    {
        $ymlFiles = array();

        $finder = new Finder();
        $finder->files();
        $finder->followLinks();
        $finder->in($this->sourcePaths);
        $finder->sortByName();

        foreach ($this->fileNamePatterns as $filePattern) {
            $finder->name($filePattern.'.yml');
        }

        if ($finder->count() < 1) {
            return array();
        }

        foreach ($finder as $file) {
            $fileName = str_replace('.yml', '', $file->getFilename());

            /** @var \Symfony\Component\Finder\SplFileInfo $file */
            $ymlFiles[$fileName][] = $file->getRealPath();
        }

        return $ymlFiles;
    }

  /**
   * Alternative to array_merge_recursive without duplicates.
   *
   * array_merge_recursive does indeed merge arrays, but it converts values with
   * duplicate keys to arrays rather than overwriting the value in the first
   * array with the duplicate value in the second array, as array_merge does.
   * I.e., with array_merge_recursive, this happens (documented behavior):
   *
   * array_merge_recursive(array('key' => 'value 1'),
   *   array('key' => 'value 2'));
   * => array('key' => array('value 1', 'value 2'));
   *
   * arrayMergeRecursiveDistinct does not change the datatypes of the values
   * in the arrays. Matching keys' values in the second array overwrite those in
   * the first array, as is the case with array_merge, i.e.:
   *
   * arrayMergeRecursiveDistinct(array('key' => 'value 1'),
   *   array('key' => 'value 2'));
   * => array('key' => array('value 2'));
   *
   * @param array $array1
   * @param array $array2
   *
   * @return array
   *
   * @see https://www.php.net/manual/en/function.array-merge-recursive.php#92195
   */
  protected function arrayMergeRecursiveDistinct( array &$array1, array &$array2 )
  {
    $merged = $array1;

    foreach ($array2 as $key => &$value) {
      if ( is_array ( $value ) && isset ($merged [$key]) && is_array ($merged [$key])) {
        $merged [$key] = $this->arrayMergeRecursiveDistinct ($merged [$key], $value);
      }
      else {
        $merged [$key] = $value;
      }
    }
    return $merged;
  }

}
