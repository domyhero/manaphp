<?php
namespace Application\Home\Models;

/**
 * Class Application\Home\Models\Country
 *
 * @package Application\Home\Models
 *
 * @property \Application\Home\Models\City $cities
 */
class Country extends ModelBase
{
    public $country_id;
    public $country;
    public $last_update;

    /**
     * @return \ManaPHP\Model\CriteriaInterface
     */
    public function getCities()
    {
        return $this->hasMany(City::class);
    }
}