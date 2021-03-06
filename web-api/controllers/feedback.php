<?php

use app\exceptions\ApiException;
use app\exceptions\ServiceException;
use Symfony\Component\HttpFoundation\Request;
use app\models\People;

$app->post('/reactivate', function(Request $request) use ($app) {
    $r = $request->request;

    $id   = (int) $r->get('id');
    $code = $r->get('code');

    if (empty($id) || empty($code)) {
        throw new ApiException('Missing required field');
    }

    // @TODO ideally id should be person id and not match id
    $match = $app['matches']->get($id, true, false);

    $hash = $app['mailer']->createHashCode($match['host']['email']);
    if ($hash != $code) {
        $this->app['monolog']->warning("Feedback request with invalid code [{$code}] != [{$hash}] for person [{$match['host']['id']}]");
        throw new ApiException('Invalid code!');
    }

    if (((int) $match['host']['status']) !== \app\models\People::STATUS_USED) {
        throw new ApiException('Not able to reactivate this Person');
    }

    $host_id = $match['host_id'];
    $data = ['status' => People::STATUS_ACTIVE];
    $result = $app['people']->update($host_id, $data);
    if (!$result) {
        throw new ServiceException('Could not update person');
    }

    return $app->json('OK');
});

$app->post('/feedback', function(Request $request) use ($app) {
    $r = $request->request;

    $id   = (int) $r->get('id');
    $code = $r->get('code');
    $status = (int) $r->get('status');

    if (empty($id) || empty($code) || empty($status)) {
        throw new ApiException('Missing required field');
    }

    $match = $app['matches']->get($id, true, false);

    $hash = $app['mailer']->createHashCode($match['host']['email']);
    if ($hash != $code) {
        $this->app['monolog']->warning("Feedback request with invalid code [{$code}] != [{$hash}] for match [{$id}]");
        throw new ApiException('Invalid code!');
    }

    if ($match['status'] != \app\models\Matches::STATUS_NEW) {
        throw new ApiException('Match is not "NEW"');
    }

    $data = ['status' => $status];
    $result = $app['matches']->update($id, $data);
    if (!$result) {
        throw new ServiceException("Failed to update match {$id}");
    }

    return $app->json('OK');
});