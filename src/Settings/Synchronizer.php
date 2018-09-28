<?php

declare(strict_types=1);

/**
 * This file is part of Laravel Scout Extended.
 *
 * (c) Algolia Team <contact@algolia.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Algolia\LaravelScoutExtended\Settings;

use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Algolia\AlgoliaSearch\Interfaces\IndexInterface;

/**
 * @internal
 */
class Synchronizer
{
    /**
     * @var \Algolia\LaravelScoutExtended\Settings\Compiler
     */
    private $compiler;

    /**
     * @var \Algolia\LaravelScoutExtended\Settings\Encrypter
     */
    private $encrypter;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $files;

    /**
     * @var \Algolia\LaravelScoutExtended\Settings\RemoteRepository
     */
    private $remoteRepository;

    /**
     * Synchronizer constructor.
     *
     * @param \Algolia\LaravelScoutExtended\Settings\Compiler $compiler
     * @param \Algolia\LaravelScoutExtended\Settings\Encrypter $encrypter
     * @param \Illuminate\Filesystem\Filesystem $files
     * @param \Algolia\LaravelScoutExtended\Settings\RemoteRepository $remoteRepository
     *
     * @return void
     */
    public function __construct(
        Compiler $compiler,
        Encrypter $encrypter,
        Filesystem $files,
        RemoteRepository $remoteRepository
    ) {
        $this->compiler = $compiler;
        $this->encrypter = $encrypter;
        $this->remoteRepository = $remoteRepository;
        $this->files = $files;
    }

    /**
     * Analyses the settings of the given index.
     *
     * @param \Algolia\AlgoliaSearch\Interfaces\IndexInterface $index
     *
     * @return \Algolia\LaravelScoutExtended\Settings\StateResponse
     */
    public function analyse(IndexInterface $index): StateResponse
    {
        $settings = new Settings($this->remoteRepository->from($index), $this->remoteRepository->defaults());

        $path = config_path('scout-'.Str::lower($index->getIndexName()).'.php');

        return new StateResponse($this->encrypter, $this->files, $settings, $path);
    }

    /**
     * Downloads the settings of the given index.
     *
     * @param \Algolia\AlgoliaSearch\Interfaces\IndexInterface $index
     *
     * @return void
     */
    public function download(IndexInterface $index): void
    {
        $settings = new Settings($this->remoteRepository->from($index), $this->remoteRepository->defaults());

        $path = config_path('scout-'.Str::lower($index->getIndexName()).'.php');

        $this->compiler->compile($settings, $path);

        $userData = $this->encrypter->local($path);

        $index->setSettings(['userData' => $userData,]);
    }

    /**
     * Uploads the settings of the given index.
     *
     * @param \Algolia\AlgoliaSearch\Interfaces\IndexInterface $index
     *
     * @return void
     */
    public function upload(IndexInterface $index): void
    {
        $path = config_path('scout-'.Str::lower($index->getIndexName()).'.php');

        $settings = require $path;

        $userData = $this->encrypter->with($settings);

        $index->setSettings(array_merge($settings, ['userData' => $userData,]));
    }
}