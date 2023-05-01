<?php

namespace mataluis2k\shipwire;

use mataluis2k\shipwire\base\ShipwireComponent;

/**
 * Class ReturnAuthorization
 * @package mataluis2k\shipwire
 * @author Sebastian Thierer <sebas@mataluis2k.com>
 */
class ReturnAuthorization extends ShipwireComponent
{
    /**
     * Lists all returns depending on your parameters.
     * @param array $params
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function listing($params = [], $page = 0, $limit = 50)
    {
        return $this->get('returns', $params, $page, $limit);
    }

    /**
     * Gets information about the return
     * @param $returnId
     * @param bool $expand
     * @return array
     * @throws exceptions\InvalidAuthorizationException
     * @throws exceptions\InvalidRequestException
     * @throws exceptions\ShipwireConnectionException
     */
    public function returnDetails($returnId, bool $expand = false): array
    {
        $params = [];
        if ($expand) {
            $params['expand'] = 'all';
        }
        return $this->get($this->getRoute('returns/{id}', $returnId), $params);
    }

    /**
     * Cancels a Return
     * @param $returnId
     * @return bool
     * @throws exceptions\InvalidAuthorizationException
     * @throws exceptions\InvalidRequestException
     * @throws exceptions\ShipwireConnectionException
     */
    public function cancel($returnId)
    {
        $ret = $this->post($this->getRoute('returns/{id}/cancel', $returnId), [], null, true);
        return $ret['status'] == 200;
    }

    /**
     * Transform the route with the given returnId
     * @param $route
     * @param null $returnId
     * @return string
     */
    private function getRoute($route, $returnId = null)
    {
        if ($returnId !== null) {
            return strtr($route, ['{id}' => $returnId]);
        }
        return $route;
    }


    /**
     * Get the list of holds, if any, on a return
     * @param $returnId
     * @param bool $includeCleared
     * @param array $params
     * @param int $page
     * @param int $limit
     * @return array
     * @throws exceptions\InvalidAuthorizationException
     * @throws exceptions\InvalidRequestException
     * @throws exceptions\ShipwireConnectionException
     */
    public function holds($returnId, bool $includeCleared = false, array $params = [], int $page = 0, int $limit = 50)
    {
        if (!isset($params['includeCleared'])) {
            $params['includeCleared'] = $includeCleared ? 1 : 0;
        }
        return $this->get($this->getRoute('returns/{id}/holds', $returnId), $params, $page, $limit);
    }


    /**
     * Get the product details for this return
     * @param $returnId
     * @param array $params
     * @param int $page
     * @param int $limit
     * @return array
     * @throws exceptions\InvalidAuthorizationException
     * @throws exceptions\InvalidRequestException
     * @throws exceptions\ShipwireConnectionException
     */
    public function items($returnId, array $params = [], int $page = 0, int $limit = 50)
    {
        return $this->get($this->getRoute('returns/{id}/items', $returnId), $params, $page, $limit);
    }

    /**
     * Get tracking information for a specific return.
     * @param $returnId
     * @param array $params
     * @param int $page
     * @param int $limit
     * @return array
     * @throws exceptions\InvalidAuthorizationException
     * @throws exceptions\InvalidRequestException
     * @throws exceptions\ShipwireConnectionException
     */
    public function tracking($returnId, array $params = [], int $page = 0, int $limit = 50)
    {
        return $this->get($this->getRoute('returns/{id}/trackings', $returnId), $params, $page, $limit);
    }

    /**
     * Get the shipping labels for this return.
     * @param $returnId
     * @param array $params
     * @param int $page
     * @param int $limit
     * @return array
     * @throws exceptions\InvalidAuthorizationException
     * @throws exceptions\InvalidRequestException
     * @throws exceptions\ShipwireConnectionException
     */
    public function labels($returnId, array $params = [], int $page = 0, int $limit = 50)
    {
        return $this->get($this->getRoute('returns/{id}/labels', $returnId), $params, $page, $limit);
    }

    /**
     * Get the shipping labels for this return.
     * @param $returnId
     * @param array $params
     * @param int $page
     * @param int $limit
     * @return array
     * @throws exceptions\InvalidAuthorizationException
     * @throws exceptions\InvalidRequestException
     * @throws exceptions\ShipwireConnectionException
     */
    public function labelsPDF($returnId, $pdfFileResource) // array $params = [], int $page = 0, int $limit = 50
    {
        $route =$this->getRoute('returns/{id}/labels', $returnId);
        return $this->_connector->download($route);
    }

    /**
     * NOTE: THIS IS A v3.1 FEATURE ONLY!!
     * Generate return labels for multiple orders with the result combined into a single PDF document.
     * The Job will return a combined PDF download URL.
     * @param array $returnIds
     * @param array $params
     * @return array
     * @throws exceptions\InvalidAuthorizationException
     * @throws exceptions\InvalidRequestException
     * @throws exceptions\ShipwireConnectionException
     */
    public function generateLabels(array $returnIds, array $params = [])
    {
        //return $this->post('returns/generateLabels', $params, json_encode($returnIds));
        return $this->_connector->api('returns/generateLabels', $params, ShipwireConnector::POST, json_encode($returnIds), true, false, 'v3.1');
    }

    /**
     * Creates a return.
     * @param $returnData
     * @param array $params
     * @return array
     * @throws exceptions\InvalidAuthorizationException
     * @throws exceptions\InvalidRequestException
     * @throws exceptions\ShipwireConnectionException
     */
    public function create($returnData, array $params = [])
    {
        return $this->post('returns', $params, json_encode($returnData));
    }

    /**
     * Errors related to validation errors
     * @var array
     */
    public array $errors=[];
}
