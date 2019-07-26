<?php
namespace Entity;

/**
 * @DEM_table=projects_microbit
 * @DEM_repo=RepoProjectMicroBit
 */
class ProjectMicroBit implements \JsonSerializable
{
    /**
     * @DEM_name=id 
     * @var integer
     */
    private $id;
    /**
     * @DEM_name=user 
     * @var User
     */
    private $user = null;
    /**
     * @DEM_name=project_name 
     * @var string
     */
    private $name = "Unamed";
    /**
     * @DEM_name=project_description 
     * @var string
     */
    private $description = "No description";
    /**
     * @DEM_name=date_updated 
     * @var datetime
     */
    private $dateUpdated;
    /**
     * @DEM_name=code 
     * @var string
     */
    private $code = "";
    /**
     * @DEM_name=is_public 
     * @var bool
     */
    private $public = 0;
    /**
     * @DEM_name=link 
     * @var string
     */
    private $link = null;


    /**
     * ProjectMicroBit constructor
     * @param datetime $dateUpdated
     * @param string $code
     */
    public function __construct($name = '', $description = '')
    {
        $this->name = ($name === '') ?  "Unamed" : $name;
        $this->description = ($description === '') ? "No description" : $description;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return integer
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return datetime
     */
    public function getDateUpdated()
    {
        return $this->dateUpdated;
    }

    /**
     * @param datetime $dateUpdated
     */
    public function setDateUpdated($dateUpdated)
    {
        $this->dateUpdated = $dateUpdated;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return bool
     */
    public function isPublic($database = false)
    {
        if ($database === true)
            return ($this->public == true) ? 1 : 0;
        else
            return $this->public;
    }

    /**
     * @param string $code
     */
    public function setPublic($isPublic)
    {
        if (is_numeric($isPublic)) {
            $isPublic = ($isPublic === 0) ? false : true;
        }
        $this->public = $isPublic;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param string $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    private function encodeURIComponent($str)
    {
        $revert = array('%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')');
        return strtr(rawurlencode($str), $revert);
    }

    public function jsonSerialize()
    {
        return [
            'user' => $this->getUser(),
            'name' => $this->encodeURIComponent($this->getName()),
            'description' => $this->encodeURIComponent($this->getDescription()),
            'dateUpdated' => $this->getDateUpdated(),
            'code' => $this->getCode(),
            'isPublic' => $this->isPublic(),
            'link' => $this->getLink()
        ];
    }
}
