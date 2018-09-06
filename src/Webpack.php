<?php namespace NSRosenqvist\Phulp;

use Phulp\Source;

class Webpack implements \Phulp\PipeInterface
{
    private $bin;
    private $config;
    private $env;
    private $mode = 'none';

    public function __construct(array $config = [], string $bin = 'webpack')
    {
        $this->bin = $bin;
        $this->config = $config;
    }

    public function setEnv($env)
    {
        $this->env = (array) $env;
    }

    public function setMode(string $mode)
    {
        $this->mode = $mode;
    }

    public function execute(Source $src)
    {
        // Start building command
        $baseCmd = $this->bin;
        $baseCmd .= ' --mode '.$this->mode;
        $baseCmd .= ' --display verbose';

        // Set environment vars
        if ($this->env) {
            foreach ($this->env as $key => $val) {
                $baseCmd .= ' --env.'.$key.'='.$val;
            }
        }

        // Load config if set
        if (! empty($this->config)) {
            // If output options are set that manipulate the filename or path we
            // save those rules to apply them later
            if (isset($this->config['output'])) {
                $rename = [];

                // Save rename rule for directory
                if (isset($this->config['output']['path'])) {
                    $rename['directory'] = $this->config['output']['path'];
                    unset($this->config['output']['path']);
                }
                // Save rename rule for filename
                elseif (isset($this->config['output']['filename'])) {
                    $rename['filename'] = $this->config['output']['filename'];
                    unset($this->config['output']['filename']);
                }

                if (empty($this->config['output'])) {
                    unset($this->config['output']);
                }
            }

            if (! empty($this->config)) {
                // Create a temporary config file
                $tmpConfig = tempnam(sys_get_temp_dir(), 'tmpConfig');
                file_put_contents($tmpConfig, 'module.exports = '.json_encode($this->config));

                $baseCmd .= ' --config '.$tmpConfig;
            }
        }

        // Process files
        foreach ($src->getDistFiles() as $key => $file) {
            $cmd = $baseCmd;

            // Input
            $input = tempnam($file->getFullPath(), $file->getName());
            rename($input, $input = $input.'.tmp');
            file_put_contents($input, $file->getContent());
            $cmd .= ' '.$input;

            // Output
            $output = tempnam(sys_get_temp_dir(), 'output');
            $cmd .= ' -o '.$output;

            // Run webpack
            shell_exec($cmd);

            // Get output
            $file->setContent(file_get_contents($output));

            // Rename output if the properties were set in the config
            if (isset($rename) && ! empty($rename)) {
                $directory = $rename['directory'] ?? dirname($file->getDistpathname());
                $filename = $rename['filename'] ?? basename($file->getDistpathname());

                $file->setDistpathname($directory.DIRECTORY_SEPARATOR.$filename);
            }

            // Cleanup
            unlink($output);
            unlink($input);
        }

        // Delete temporary config file
        if (isset($tmpConfig)) {
            unlink($tmpConfig);
        }
    }
}
