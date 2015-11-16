<?php

namespace CU\Soracom;
use Monolog\Logger;
use GuzzleHttp\Client as ApiClient;
use GuzzleHttp\Exception\ClientException;

class Client
{
    const API_BASE_URL = 'https://api.soracom.io/v1/';

    public $logger;
    public $api;
    public $api_headers;
    public $auth;

    public function __construct($email = null, $password = null, $endpoint = null)
    {
        if (!$email) {
            $email = getenv('SORACOM_EMAIL');
        }

        if (!$password) {
            $password = getenv('SORACOM_PASSWORD');
        }

        if (!$endpoint) {
            if (getenv('SORACOM_ENDPOINT')) {
                $endpoint = getenv('SORACOM_ENDPOINT');
            } else {
                $endpoint = self::API_BASE_URL;
            }
        }

        $this->logger = new Logger('soracom');
        if ($email && $password) {
            $this->auth = $this->authenticate($email, $password, $endpoint);
        } else {
            throw new \Exception('Could not find any credentials(email & password)');
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Soracom-API-Key' => $this->auth['apiKey'],
            'X-Soracom-Token' => $this->auth['token'],
        ];

        $this->api = new ApiClient(['base_uri' => $endpoint, 'headers' => $headers]);
    }

    public function listSubscribers(array $query = [])
    {
        if (!isset($query['operatorId'])) {
            $query['operatorId'] = $this->auth['operatorId'];
        }

        if (!isset($query['limit'])) {
            $query['limit'] = 1024;
        }

        $path = 'subscribers';

        if (isset($query['filter'])) {
            $filter = $query['filter'];
            unset($query['filter']);
            switch($filter['key']) {
            case 'imsi':
                $path = sprintf('subscribers/%s', $filter['value']);
                break;
            case 'msisdn':
                $path = sprintf('subscribers/msisdn/%s', $filter['value']);
                break;
            case 'status':
                $query['status_filter'] = $filter['value'];
                break;
            case 'speed_class':
                $query['speed_class_filter'] = $filter['value'];
                break;
            default:
                $query['tag_name'] = $filter['key'];
                $query['tag_value'] = $filter['value'];
                $query['tag_value_match_mode'] = array_key_exists('mode', $filter) ? $filter['mode'] : 'exact';
                break;
            }
        }

        try {
            $response = $this->api->request('GET', $path, ['query' => $query]);
            $body = $response->getBody();
        } catch(ClientException $e) {
            $body = '{}';
        }

        return json_decode((string) $body, true);
    }

    public function subscribers(array $options = [])
    {
        return $this->listSubscribers($options);
    }

    public function registerSubscriber(array $options = [])
    {
        if (isset($options['groupId'])) {
            $params['groupId'] = $options['groupId'];
        }

        $path = sprintf('subscribers/%s/register', $options['imsi']);
        unset($options['imsi']);

        $response = $this->api->request('POST', $path, ['json' => $options]);
        $body = $response->getBody();

        return json_decode((string) $body, true);
    }

    public function activateSubscriber($imsis)
    {
        if (!is_array($imsis)) {
            $imsis = [$imsis];
        }

        $result = [];
        foreach ($imsis as $imsi) {
            $path = sprintf('subscribers/%s/activate', $imsi);
            try {
                $response = $this->api->request('POST', $path, ['json' => []]);
            } catch(ClientException $e) {
                $response = $e->getResponse();
            }
            $result[] = json_decode((string) $response->getBody(), true);
        }

        return $result;
    }

    public function deactivateSubscriber($imsis)
    {
        if (!is_array($imsis)) {
            $imsis = [$imsis];
        }

        $result = [];
        foreach ($imsis as $imsi) {
            $path = sprintf('subscribers/%s/deactivate', $imsi);
            try {
                $response = $this->api->request('POST', $path, ['json' => []]);
            } catch(ClientException $e) {
                $response = $e->getResponse();
            }
            $result[] = json_decode((string) $response->getBody(), true);
        }

        return $result;
    }

    public function enableTermination($imsis)
    {
        if (!is_array($imsis)) {
            $imsis = [$imsis];
        }

        $result = [];
        foreach ($imsis as $imsi) {
            $path = sprintf('subscribers/%s/enable_termination', $imsi);
            try {
                $response = $this->api->request('POST', $path, ['json' => []]);
            } catch(ClientException $e) {
                $response = $e->getResponse();
            }
            $result[] = json_decode((string) $response->getBody(), true);
        }

        return $result;
    }

    public function disableTermination($imsis)
    {
        if (!is_array($imsis)) {
            $imsis = [$imsis];
        }

        $result = [];
        foreach ($imsis as $imsi) {
            $path = sprintf('subscribers/%s/disable_termination', $imsi);
            try {
                $response = $this->api->request('POST', $path, ['json' => []]);
            } catch(ClientException $e) {
                $response = $e->getResponse();
            }
            $result[] = json_decode((string) $response->getBody(), true);
        }

        return $result;
    }

    public function updateSubscriberTags($imsis, $tags)
    {
        if (!is_array($imsis)) {
            $imsis = [$imsis];
        }

        $result = [];
        foreach ($imsis as $imsi) {
            $path = sprintf('subscribers/%s/update_tags', $imsi);
            try {
                $response = $this->api->request('POST', $path, ['json' => $tags]);
            } catch(ClientException $e) {
                $response = $e->getResponse();
            }
            $result[] = json_decode((string) $response->getBody(), true);
        }

        return $result;
    }

    public function deleteSubscriberTags($imsis, $tag_name)
    {
        if (!is_array($imsis)) {
            $imsis = [$imsis];
        }

        $result = [];
        foreach ($imsis as $imsi) {
            $path = sprintf('subscribers/%s/tags/%s', $imsi, urlencode($tag_name));
            try {
                $response = $this->api->request('DELETE', $path);
            } catch(ClientException $e) {
                $response = $e->getResponse();
            }
            $result[] = json_decode((string) $response->getBody(), true);
        }

        return $result;
    }

    public function updateSubscriberSpeedClass($imsis, $speed_class)
    {
        if (!is_array($imsis)) {
            $imsis = [$imsis];
        }

        $result = [];
        foreach ($imsis as $imsi) {
            $path = sprintf('subscribers/%s/update_speed_class', $imsi);
            try {
                $response = $this->api->request('POST', $path, ['json' => ['speedClass' => $speed_class]]);
            } catch(ClientException $e) {
                $response = $e->getResponse();
            }
            $result[] = json_decode((string) $response->getBody(), true);
        }

        return $result;
    }

    public function setExpiryTime($imsis, $expiry_time)
    {
        if (!is_array($imsis)) {
            $imsis = [$imsis];
        }

        $result = [];
        foreach ($imsis as $imsi) {
            $path = sprintf('subscribers/%s/set_expiry_time', $imsi);
            try {
                $response = $this->api->request('POST', $path, ['json' => ['expiryTime' => $expiry_time]]);
            } catch(ClientException $e) {
                $response = $e->getResponse();
            }
            $result[] = json_decode((string) $response->getBody(), true);
        }

        return $result;
    }

    public function unsetExpiryTime($imsis)
    {
        if (!is_array($imsis)) {
            $imsis = [$imsis];
        }

        $result = [];
        foreach ($imsis as $imsi) {
            $path = sprintf('subscribers/%s/unset_expiry_time', $imsi);
            try {
                $response = $this->api->request('POST', $path, ['json' => []]);
            } catch(ClientException $e) {
                $response = $e->getResponse();
            }
            $result[] = json_decode((string) $response->getBody(), true);
        }

        return $result;
    }

    public function setGroup($imsi, $group_id)
    {
        $path = sprintf('subscribers/%s/set_group', $imsi);
        $response = $this->api->request('POST', $path, ['json' => ['groupId' => $group_id]]);

        return json_decode((string) $response->getBody(), true);
    }

    public function unsetGroup($imsi)
    {
        $path = sprintf('subscribers/%s/unset_group', $imsi);
        $response = $this->api->request('POST', $path, ['json' => []]);

        return json_decode((string) $response->getBody(), true);
    }

    public function listGroups($group_id = null)
    {
        $path = 'groups';

        if ($group_id) {
            $path = sprintf('%s/%s', $path, $group_id);
        }

        try {
            $response = $this->api->request('GET', $path);
            $body = $response->getBody();
        } catch(ClientException $e) {
            $body = '{}';
        }

        return json_decode((string) $body, true);
    }

    public function createGroup($tags = null)
    {
        $payload = $tags ? ['tags' => $tags] : [];
        $path = 'groups';
        $response = $this->api->request('POST', $path, ['json' => $payload]);

        return json_decode((string) $response->getBody(), true);
    }

    public function deleteGroup($group_id)
    {
        $path = sprintf('groups/%s', $group_id);
        $response = $this->api->request('DELETE', $path, ['json' => []]);

        return json_decode((string) $response->getBody(), true);
    }

    public function listSubscribersInGroup($group_id)
    {
        $path = sprintf('groups/%s/subscribers', $group_id);
        $response = $this->api->request('GET', $path);

        return json_decode((string) $response->getBody(), true);
    }

    public function updateGroupConfiguration($group_id, $namespace, $params)
    {
        // TODO test
        $path = sprintf('groups/%s/configuration/%s', $group_id, $namespace);
        $response = $this->api->request('PUT', $path, ['json' => $params]);

        return json_decode((string) $response->getBody(), true);
    }

    public function deleteGroupConfiguration($group_id, $namespace, $name)
    {
        // TODO test
        $path = sprintf('groups/%s/configuration/%s/%s', $group_id, $namespace, $name);
        $response = $this->api->request('DELETE', $path);

        return json_decode((string) $response->getBody(), true);
    }

    public function updateGroupTags($group_id, $tags = [])
    {
        $path = sprintf('groups/%s/tags', $group_id);
        $response = $this->api->request('PUT', $path, ['json' => $tags]);

        return json_decode((string) $response->getBody(), true);
    }

    public function deleteGroupTags($group_id, $name)
    {
        $path = sprintf('groups/%s/tags/%s', $group_id, urlencode($name));
        $response = $this->api->request('DELETE', $path, ['json' => []]);

        return json_decode((string) $response->getBody(), true);
    }

    public function listEventHandlers(array $options = [])
    {
        if (isset($options['handler_id'])) {
            $path = sprintf('event_handlers/%s', $options['handler_id']);
        } elseif (isset($options['imsi'])) {
            $path = sprintf('event_handlers/subscribers/%s', $options['imsi']);
        } elseif (isset($options['target'])) {
            $query = ['target' => $options['target']];
            $path = 'event_handlers';
        } else {
            $path = 'event_handlers';
        }

        try {
            if (isset($query)) {
                $response = $this->api->request('GET', $path, ['query' => $query]);
            } else {
                $response = $this->api->request('GET', $path);
            }
            $body = $response->getBody();
        } catch(ClientException $e) {
            $body = '{}';
        }

        return json_decode((string) $body, true);
    }

    public function createEventHandler($req)
    {
        // TODO test
        $path = 'event_handlers';
        $response = $this->api->request('POST', $path, ['json' => $req]);

        return json_decode((string) $response->getBody(), true);
    }

    public function getEventHandler($handler_id)
    {
        $path = sprintf('event_handlers/%s', $handler_id);
        $response = $this->api->request('GET', $path);

        return json_decode((string) $response->getBody(), true);
    }

    public function deleteEventHandler($handler_id)
    {
        $path = sprintf('event_handlers/%s', $handler_id);
        $response = $this->api->request('DELETE', $path, ['json' => []]);

        return json_decode((string) $response->getBody(), true);
    }

    public function updateEventHandler($handler_id, $params)
    {
        $path = sprintf('event_handlers/%s', $handler_id);
        $response = $this->api->request('PUT', $path, ['json' => $params]);

        return json_decode((string) $response->getBody(), true);
    }

    public function getAirUsage(array $query = [])
    {
        if (!isset($query['from'])) {
            $query['from'] = time() - 24 * 60 * 60;
        }

        if (!isset($query['to'])) {
            $query['to'] = time();
        }

        if (!isset($query['period'])) {
            $query['period'] = 'minutes';
        }

        $path = sprintf('stats/air/subscribers/%s', $query['imsi']);
        unset($query['imsi']);
        $response = $this->api->request('GET', $path, ['query' => $query]);

        return json_decode((string) $response->getBody(), true);
    }

    public function getBeamUsage(array $query = [])
    {
        if (!isset($query['from'])) {
            $query['from'] = time() - 24 * 60 * 60;
        }

        if (!isset($query['to'])) {
            $query['to'] = time();
        }

        if (!isset($query['period'])) {
            $query['period'] = 'minutes';
        }

        $path = sprintf('stats/beam/subscribers/%s', $query['imsi']);
        unset($query['imsi']);
        $response = $this->api->request('GET', $path, ['query' => $query]);

        return json_decode((string) $response->getBody(), true);
    }

    public function exportAirUsage(array $payload = [])
    {
        if (!isset($payload['operator_id'])) {
            $payload['operator_id'] = $this->auth['operatorId'];
        }

        if (!isset($payload['from'])) {
            $payload['from'] = strtotime('first day of this month');
        }

        if (!isset($payload['to'])) {
            $payload['to'] = time();
        }

        if (!isset($payload['period'])) {
            $payload['period'] = 'day';
        }

        $path = sprintf('stats/air/operators/%s/export', $payload['operator_id']);
        unset($payload['operator_id']);
        $response = $this->api->request('POST', $path, ['json' => $payload]);

        return file_get_contents(json_decode((string) $response->getBody(), true)['url']);
    }

    public function exportBeamUsage(array $payload = [])
    {
        if (!isset($payload['operator_id'])) {
            $payload['operator_id'] = $this->auth['operatorId'];
        }

        if (!isset($payload['from'])) {
            $payload['from'] = strtotime('first day of this month');
        }

        if (!isset($payload['to'])) {
            $payload['to'] = time();
        }

        if (!isset($payload['period'])) {
            $payload['period'] = 'day';
        }

        $path = sprintf('stats/beam/operators/%s/export', $payload['operator_id']);
        unset($payload['operator_id']);
        $response = $this->api->request('POST', $path, ['json' => $payload]);

        return file_get_contents(json_decode((string) $response->getBody(), true)['url']);
    }

    public function getSupportUrl(array $options = [])
    {
        if (!isset($options['return_to'])) {
            $options['return_to'] = 'https://soracom.zendesk.com/hc/ja/requests';
        }

        $path = sprintf('operators/%s/support/token', $this->auth['operatorId']);
        $response = $this->api->request('POST', $path);
        $token = json_decode((string) $response->getBody(), true)['token'];
 
        return sprintf('https://soracom.zendesk.com/access/jwt?jwt=%s&return_to=%s', $token, $options['return_to']);
    }

    public function apiKey()
    {
        return $this->auth['apiKey'];
    }

    public function operatorId()
    {
        return $this->auth['operatorId'];
    }

    private function authenticate($email, $password, $endpoint)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        $client = new ApiClient(['base_uri' => $endpoint, 'headers' => $headers]);

        $response = $client->request('POST', 'auth', ['json' => ['email' => $email, 'password' => $password]]);

        if ($response->getStatusCode() != 200) {
            throw new Exception($response->getReasonPhrase());
        }

        $body = $response->getBody();

        return json_decode((string) $body, true);
    }

}
