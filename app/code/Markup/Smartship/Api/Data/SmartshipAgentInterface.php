<?php
namespace Markup\Smartship\Api\Data;

interface SmartshipAgentInterface
{
    const AGENT_ID = 'agent_id';
    const NAME = 'name';
    const STREET_ADDRESS = 'street_address';
    const POSTCODE = 'postcode';
    const CITY = 'city';

    /**
     * Return agent ID.
     *
     * @return string|null
     */
    public function getAgentId();

    /**
     * Set agent ID.
     *
     * @param string|null $value
     * @return $this
     */
    public function setAgentId($value);

    /**
     * Return agent name.
     *
     * @return string|null
     */
    public function getName();

    /**
     * Set agent name.
     *
     * @param string|null $value
     * @return $this
     */
    public function setName($value);

    /**
     * Return agent street address.
     *
     * @return string|null
     */
    public function getStreetAddress();

    /**
     * Set agent street address.
     *
     * @param string|null $value
     * @return $this
     */
    public function setStreetAddress($value);

    /**
     * Return agent postcode.
     *
     * @return string|null
     */
    public function getPostcode();

    /**
     * Set agent postcode.
     *
     * @param string|null $value
     * @return $this
     */
    public function setPostcode($value);

    /**
     * Return agent city.
     *
     * @return string|null
     */
    public function getCity();

    /**
     * Set agent city.
     *
     * @param string|null $value
     * @return $this
     */
    public function setCity($value);
}
