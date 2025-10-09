<?php

namespace App\Entity;

use App\Repository\EquipementsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EquipementsRepository::class)]
class Equipements
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id_equipement = null;


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $machine = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $modele = null;

    #[ORM\Column(nullable: true)]
    private ?string $numero_de_serie = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statut = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $etat = null;


    public function getIdEquipement(): ?int
    {
        return $this->id_equipement;
    }

    public function getMachine(): ?string
    {
        return $this->machine;
    }

    public function setMachine(?string $machine): static
    {
        $this->machine = $machine;

        return $this;
    }

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(?string $modele): static
    {
        $this->modele = $modele;

        return $this;
    }

    public function getNumeroDeSerie(): ?string
    {
        return $this->numero_de_serie;
    }

    public function setNumeroDeSerie(?string $numero_de_serie): static
    {
        $this->numero_de_serie = $numero_de_serie;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(?string $etat): static
    {
        $this->etat = $etat;

        return $this;
    }
}
