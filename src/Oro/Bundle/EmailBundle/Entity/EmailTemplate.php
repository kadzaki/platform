<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * EmailTemplate
 *
 * @ORM\Table(name="oro_email_template",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="UQ_NAME", columns={"name", "entityName"})},
 *      indexes={@ORM\Index(name="email_name_idx", columns={"name"}),
 *          @ORM\Index(name="email_is_system_idx", columns={"isSystem"}),
 *          @ORM\Index(name="email_entity_name_idx", columns={"entityName"})})
 * @ORM\Entity(repositoryClass="Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository")
 * @Gedmo\TranslationEntity(class="Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation")
 * @Config(
 *  defaultValues={
 *      "entity"={"label"="Email Template", "plural_label"="Email Templates"},
 *      "security"={
 *          "type"="ACL",
 *          "group_name"=""
 *      }
 *  }
 * )
 */
class EmailTemplate implements Translatable
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isSystem", type="boolean")
     */
    protected $isSystem;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent", type="integer", nullable=true)
     */
    protected $parent;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     */
    protected $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     * @Gedmo\Translatable
     */
    protected $content;

    /**
     * @var string
     *
     * @ORM\Column(name="entityName", type="string", length=255, nullable=true)
     */
    protected $entityName;

    /**
     * Template type:
     *  - html
     *  - text
     *
     * @ORM\Column(name="type", type="string", length=20)
     * @var string
     */
    protected $type;

    /**
     * @Gedmo\Locale
     */
    protected $locale;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation",
     *     mappedBy="object",
     *     cascade={"persist", "remove"}
     * )
     * @Assert\Valid(deep = true)
     */
    protected $translations;

    /**
     * @param $name
     * @param string $content
     * @param string $type
     * @param bool $isSystem
     * @internal param $entityName
     */
    public function __construct($name = '', $content = '', $type = 'html', $isSystem = false)
    {
        // isSystem can be overridden from email template
        $this->isSystem = $isSystem;

        foreach (array('subject', 'entityName', 'isSystem') as $templateParam) {
            if (preg_match('#@' . $templateParam . '\s?=\s?(.*)\n#i', $content, $match)) {
                $this->$templateParam = trim($match[1]);
                $content = trim(str_replace($match[0], '', $content));
            }
        }

        $this->type = $type;
        $this->name = $name;
        $this->content = $content;
        $this->translations = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return EmailTemplate
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set parent
     *
     * @param integer $parent
     * @return EmailTemplate
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return integer
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return EmailTemplate
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return EmailTemplate
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set entityName
     *
     * @param string $entityName
     * @return EmailTemplate
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;

        return $this;
    }

    /**
     * Get entityName
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * Set is template system
     *
     * @param boolean $isSystem
     * @return EmailTemplate
     */
    public function setIsSystem($isSystem)
    {
        $this->isSystem = $isSystem;

        return $this;
    }

    /**
     * Get is template system
     *
     * @return boolean
     */
    public function getIsSystem()
    {
        return $this->isSystem;
    }

    /**
     * Set locale
     *
     * @param mixed $locale
     * @return EmailTemplate
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale
     *
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set template type
     *
     * @param string $type
     * @return EmailTemplate
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get template type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set translations
     *
     * @param ArrayCollection $translations
     * @return EmailTemplate
     */
    public function setTranslations($translations)
    {
        /** @var EmailTemplateTranslation $translation */
        foreach ($translations as $translation) {
            $translation->setObject($this);
        }

        $this->translations = $translations;
        return $this;
    }

    /**
     * Get translations
     *
     * @return ArrayCollection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * Clone template
     */
    public function __clone()
    {
        // cloned entity will be child
        $this->parent = $this->id;
        $this->id = null;
        $this->isSystem = false;

        if ($this->getTranslations() instanceof ArrayCollection) {
            $clonedTranslations = new ArrayCollection();
            foreach ($this->getTranslations() as $translation) {
                $clonedTranslations->add(clone $translation);
            }
            $this->setTranslations($clonedTranslations);
        }
    }

    /**
     * Convert entity to string
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getName();
    }
}
