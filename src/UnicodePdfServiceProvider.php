<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf;

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use ImranDev\UnicodePdf\Console\ClearCacheCommand;
use ImranDev\UnicodePdf\Console\DiagnoseCommand;
use ImranDev\UnicodePdf\Console\FontInstallCommand;
use ImranDev\UnicodePdf\Console\FontListCommand;
use ImranDev\UnicodePdf\Console\FontsCommand;
use ImranDev\UnicodePdf\Console\GenerateCommand;
use ImranDev\UnicodePdf\Contracts\DirectionDetectorInterface;
use ImranDev\UnicodePdf\Contracts\FontRepositoryInterface;
use ImranDev\UnicodePdf\Contracts\FontResolverInterface;
use ImranDev\UnicodePdf\Contracts\ScriptDetectorInterface;
use ImranDev\UnicodePdf\Fonts\FontManager;
use ImranDev\UnicodePdf\Fonts\FontResolver;
use ImranDev\UnicodePdf\Unicode\DirectionDetector;
use ImranDev\UnicodePdf\Unicode\ScriptDetector;

class UnicodePdfServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/unicode-pdf.php',
            'unicode-pdf'
        );

        $this->app->singleton(ScriptDetectorInterface::class, ScriptDetector::class);
        $this->app->singleton(DirectionDetectorInterface::class, DirectionDetector::class);
        $this->app->singleton(FontResolverInterface::class, FontResolver::class);

        $this->app->singleton(FontManager::class, function ($app) {
            return new FontManager($app['config']);
        });
        $this->app->bind(FontRepositoryInterface::class, FontManager::class);

        $this->app->singleton('unicode-pdf', function ($app) {
            return new UnicodePdfManager($app, $app['config']);
        });

        $this->app->alias('unicode-pdf', UnicodePdfManager::class);
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'unicode-pdf');

        $this->registerBlade();
        $this->registerMacros();
        $this->registerAbout();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/unicode-pdf.php' => config_path('unicode-pdf.php'),
            ], 'unicode-pdf-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/unicode-pdf'),
            ], 'unicode-pdf-views');

            $this->publishes([
                __DIR__.'/../resources/fonts' => storage_path('app/unicode-pdf/fonts'),
            ], 'unicode-pdf-fonts');

            $this->commands([
                DiagnoseCommand::class,
                FontsCommand::class,
                FontListCommand::class,
                FontInstallCommand::class,
                ClearCacheCommand::class,
                GenerateCommand::class,
            ]);
        }
    }

    protected function registerBlade(): void
    {
        if (! class_exists(Blade::class)) {
            return;
        }

        Blade::directive('unicodeNumerals', function (string $expression): string {
            return "<?php echo \\ImranDev\\UnicodePdf\\Support\\NumeralConverter::convert({$expression}); ?>";
        });

        $compiler = Blade::getFacadeRoot();
        if (is_object($compiler) && method_exists($compiler, 'anonymousComponentPath')) {
            Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'unicode-pdf');
        }
    }

    protected function registerMacros(): void
    {
        if (! Response::hasMacro('unicodePdf')) {
            Response::macro('unicodePdf', function (UnicodePdfDocument $document, string $filename = 'document.pdf'): Response {
                return $document->download($filename);
            });
        }
    }

    protected function registerAbout(): void
    {
        if (! class_exists(AboutCommand::class)) {
            return;
        }

        AboutCommand::add('Unicode PDF', fn (): array => [
            'Engine' => (string) config('unicode-pdf.engine', 'native'),
            'Default font' => (string) config('unicode-pdf.default_font', 'Noto Sans'),
            'Direction' => (string) config('unicode-pdf.direction', 'auto'),
        ]);
    }
}
