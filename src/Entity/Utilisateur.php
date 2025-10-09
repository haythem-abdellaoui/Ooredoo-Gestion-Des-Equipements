<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
class Utilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id_utilisateur = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    private ?string $nom_utilisateur = null;

    #[ORM\Column(length: 255)]
    private ?string $mot_de_passe = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $verification_code = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $verification_code_expires_at = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $recuperation_code = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $recuperation_code_expires_at = null;

    public function getIdUtilisateur(): ?int
    {
        return $this->id_utilisateur;
    }
    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNomUtilisateur(): ?string
    {
        return $this->nom_utilisateur;
    }

    public function setNomUtilisateur(string $nom_utilisateur): static
    {
        $this->nom_utilisateur = $nom_utilisateur;

        return $this;
    }

    public function getMotDePasse(): ?string
    {
        return $this->mot_de_passe;
    }

    public function setMotDePasse(string $mot_de_passe): static
    {
        $this->mot_de_passe = $mot_de_passe;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getVerificationCode(): ?string
    {
        return $this->verification_code;
    }

    public function setVerificationCode(?string $verification_code): static
    {
        $this->verification_code = $verification_code;

        return $this;
    }

    public function getVerificationCodeExpiresAt(): ?\DateTimeImmutable
    {
        return $this->verification_code_expires_at;
    }

    public function setVerificationCodeExpiresAt(?\DateTimeImmutable $verification_code_expires_at): static
    {
        $this->verification_code_expires_at = $verification_code_expires_at;

        return $this;
    }

    public function getRecuperationCode(): ?string
    {
        return $this->recuperation_code;
    }

    public function setRecuperationCode(?string $recuperation_code): static
    {
        $this->recuperation_code = $recuperation_code;

        return $this;
    }

    public function getRecuperationCodeExpiresAt(): ?\DateTimeImmutable
    {
        return $this->recuperation_code_expires_at;
    }

    public function setRecuperationCodeExpiresAt(?\DateTimeImmutable $recuperation_code_expires_at): static
    {
        $this->recuperation_code_expires_at = $recuperation_code_expires_at;

        return $this;
    }
}
