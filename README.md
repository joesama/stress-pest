# Load Test Report
Reporting for Load Testing Using Pest Stress

![Installation Validation](https://github.com/joesama/stress-pest/workflows/Installation%20Validation/badge.svg) 
![PHPStan](https://github.com/joesama/stress-pest/workflows/PHPStan/badge.svg)

# Installation

Simple installation via composer :

```bash
composer require "joesama/stress-pest"
```

## Usage/Examples


Use ```\Joesama\StressPest\StressCase``` in Pest.php

```
  uses(StressCase::class, Tests\TestCase::class)

```

In test file add beforeEach method to initiate all config

```
beforeEach(function () {
    $this->envConfig();
    $this->useStressReporting();
});

```


### Smoke Test
```
    $this->setDuration($duration)
        ->setConcurrent($concurrent)
        ->executeSmoke(
            [
                'login' => [
                    '/',
                    'GET',
                ]
            ],
            'smoke_'.$duration.'_'.$concurrent
        );
```

### Average Test
```
    $this->setDuration($duration)
        ->setConcurrent($concurrent)
        ->executeAverage(
            [
                'login' => [
                    '/',
                    'GET',
                ]
            ],
            'average_'.$duration.'_'.$concurrent
        );

```

### Stress Test
```
    $this->setDuration($duration)
        ->setConcurrent($concurrent)
        ->executeStress(
            [
                'login' => [
                    '/',
                    'GET',
                ]
            ],
            'stress_'.$duration.'_'.$concurrent
        );

```

In the test file add the generate report function ass the last test

```
it('generate report', function () {
    $this->generatePdfReport();
});
```

# License

joesama/stress-pest is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

