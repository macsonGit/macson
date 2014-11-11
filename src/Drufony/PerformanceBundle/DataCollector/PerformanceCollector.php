<?php

namespace Drufony\PerformanceBundle\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\Logging\DebugStack;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class PerformanceCollector extends DataCollector
{
  private $container;

  public function collect(Request $request, Response $response, \Exception $exception = null)
  {
    $last_token = $this->container->get('profiler')->find('', '', 1, 'GET', '', '');
    if (!empty($last_token)) {
      $last_token = $last_token[0]['token'];
      $profile = $this->container->get('profiler')->loadProfile($last_token);
      $this->data['memory'] = memory_get_usage(TRUE);
      $this->data['memoryPeak'] = memory_get_peak_usage(TRUE);
      $this->data['totalMemory'] = ini_get('memory_limit');
      if (!is_null($profile)) {
        $this->data['queryCount'] = $profile->getCollector('db')->getQueryCount();
        $this->data['queryTime']  = $profile->getCollector('db')->getTime();
      }
      $this->calculateInfo();
      $limits = array(
        'warning' => array(
          'memory'      => $this->container->getParameter('performance.warning.memory'),
          'query_count' => $this->container->getParameter('performance.warning.query_count'),
          'query_time'  => $this->container->getParameter('performance.warning.query_time'),
        ),
        'error' => array(
          'memory'      => $this->container->getParameter('performance.error.memory'),
          'query_count' => $this->container->getParameter('performance.error.query_count'),
          'query_time'  => $this->container->getParameter('performance.error.query_time'),
        ),
      );
      $this->setLimits($limits);
      if ($this->container->getParameter('performance.curl.enable') != 0) {
        $this->sendPost();
      }
    }
  }

  public function sendPost() { //TODO
    $ch = curl_init($this->container->getParameter('performance.curl.path'));
    curl_close($ch);
  }

  public function getWarnings() {
    return $this->data['warnings'];
  }

  public function getErrors() {
    return $this->data['errors'];
  }

  private function calculateInfo() {
    $this->data['warnings'] = 0;
    $this->data['errors'] = 0;
    if ($this->getMemory() >= $this->container->getParameter('performance.error.memory')) {
      $this->data['errors']++;
    }
    elseif ($this->getMemory() >= $this->container->getParameter('performance.warning.memory')) {
      $this->data['warnings']++;
    }
    if ($this->getPeakMemory() >= $this->container->getParameter('performance.error.memory')) {
      $this->data['errors']++;
    }
    elseif ($this->getPeakMemory() >= $this->container->getParameter('performance.warning.memory')) {
      $this->data['warnings']++;
    }
    if ($this->getQueryCount() >= $this->container->getParameter('performance.error.query_count')) {
      $this->data['errors']++;
    }
    elseif ($this->getQueryCount() >= $this->container->getParameter('performance.warning.query_count')) {
      $this->data['warnings']++;
    }
    if ($this->getQueryTime() >= $this->container->getParameter('performance.error.query_time')) {
      $this->data['errors']++;
    }
    elseif ($this->getQueryTime() >= $this->container->getParameter('performance.warning.query_time')) {
      $this->data['warnings']++;
    }
  }

  public function getQueryTime()
  {
    return number_format($this->data['queryTime'], 6);
  }

  public function getQueryCount()
  {
    return $this->data['queryCount'];
  }

  public function getMemory()
  {
    return $this->data['memory'] / (1024 * 1024);
  }

  public function getPeakMemory()
  {
    return $this->data['memoryPeak'] / (1024 * 1024);
  }

  public function getTotalMemory()
  {
    return $this->data['totalMemory'];
  }

  public function getLimits()
  {
    return $this->data['limits'];
  }

  public function setContainer(Container $container)
  {
    $this->container = $container;
  }

  public function setLimits(Array $limits)
  {
    $this->data['limits'] = $limits;
  }

  public function getName()
  {
    return 'performance';
  }
}
