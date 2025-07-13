<?php

class FeatureFlags extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Display the feature flags dashboard
     */
    public function index()
    {
        $data = $this->data;
        $data['page_title'] = 'Feature Flags';
        $data['features'] = $this->getAllFeatures();

        $this->render_template('feature_flags/index', $data);
    }

    /**
     * Get all available features and their status
     * 
     * @return array
     */
    private function getAllFeatures()
    {

        // Try to get the serialized data from Redis
        $prefix = 'unleash.client.feature.list';
        $serializedData = \App\Libraries\Cache\CacheManager::get($prefix);

        $features = [];

        // Unserialize the data
        $featureObjects = unserialize($serializedData);

        // Get the TTL (time to live) for the feature
        $ttl = $this->getFeatureTTL($prefix);

        if (is_array($featureObjects)) {
            foreach ($featureObjects as $featureName => $featureObject) {

                // Get the enabled status directly from the feature object
                $isEnabled = \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable($featureName);

                $features[] = [
                    'name' => $featureName,
                    'enabled' => $isEnabled,
                    'ttl' => $ttl
                ];
            }
        }

        return $features;
    }

    /**
     * Get the TTL (time to live) for a feature in Redis
     * 
     * @param string $key
     * @return int|null
     */
    private function getFeatureTTL($key)
    {
        $ttl = null;

        try {
            // Get the Redis connection from CacheManager
            $redis = \App\Libraries\Cache\CacheManager::$redisConnection;

            if ($redis && \App\Libraries\Cache\CacheManager::$shouldBeConnected && $redis->is_connected) {
                // Get the TTL for the key using the Redis TTL command
                // The TTL command returns:
                // - The remaining time to live in seconds if the key has an expiration
                // - -1 if the key exists but has no expiration
                // - -2 if the key does not exist
                $ttl = $redis->ttl($key);

                // If the key doesn't exist, return null
                if ($ttl === -2) {
                    $ttl = null;
                }
            }
        } catch (\Exception $e) {
            // Log the error
            log_message('error', 'Error getting TTL for feature: ' . $e->getMessage());
        }

        return $ttl;
    }

    /**
     * Clear all feature flags by deleting the Redis key
     * This is an AJAX endpoint
     * 
     * @return void
     */
    public function clearAllFeatures()
    {
        // Default response
        $response = [
            'success' => false,
            'message' => 'Failed to clear feature flags'
        ];

        try {
            // Get the Redis key for feature flags
            $key = 'unleash.client.feature.list';

            // Delete the key from Redis
            \App\Libraries\Cache\CacheManager::delete([$key]);

            $response = [
                'success' => true,
                'message' => 'All feature flags have been cleared successfully'
            ];

        } catch (\Exception $e) {
            $response['message'] = 'Error: ' . $e->getMessage();
        }

        // Return JSON response
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }
}
