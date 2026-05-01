<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\IOFactory;

class GenerateSecurityReport extends Command
{
    protected $signature   = 'report:security';
    protected $description = 'Génère le rapport de sécurité DMC DataBox au format Word (.docx)';

    private const BLEU_FONCE = '1E3A5F';
    private const BLEU_CLAIR = '2563EB';
    private const VERT       = '16A34A';
    private const GRIS_CLAIR = 'F1F5F9';
    private const GRIS_BORD  = 'CBD5E1';
    private const BLANC      = 'FFFFFF';

    public function handle(): int
    {
        $word = new PhpWord();
        $word->getSettings()->setThemeFontLang(new Language(Language::FR_FR));
        $word->setDefaultFontName('Calibri');
        $word->setDefaultFontSize(11);

        $section = $word->addSection([
            'marginTop'    => 1440,
            'marginBottom' => 1440,
            'marginLeft'   => 1200,
            'marginRight'  => 1200,
        ]);

        // ── PAGE DE TITRE ────────────────────────────────────────
        $section->addTextBreak(4);

        $r = $section->addTextRun(['alignment' => Jc::CENTER]);
        $r->addText('DMC DataBox', [
            'name' => 'Calibri', 'size' => 36, 'bold' => true, 'color' => self::BLEU_FONCE,
        ]);

        $section->addTextBreak(1);

        $r = $section->addTextRun(['alignment' => Jc::CENTER]);
        $r->addText('Rapport de Sécurité', [
            'name' => 'Calibri', 'size' => 20, 'bold' => true, 'color' => self::BLEU_CLAIR,
        ]);

        $section->addTextBreak(1);

        $r = $section->addTextRun(['alignment' => Jc::CENTER]);
        $r->addText('Plateforme de Gestion Documentaire — Groupe Coris', [
            'name' => 'Calibri', 'size' => 12, 'italic' => true, 'color' => '6B7280',
        ]);

        $section->addTextBreak(1);

        $r = $section->addTextRun(['alignment' => Jc::CENTER]);
        $r->addText(now()->format('F Y'), [
            'name' => 'Calibri', 'size' => 11, 'color' => '6B7280',
        ]);

        $section->addPageBreak();

        // ── INTRO ────────────────────────────────────────────────
        $this->titre($section, 'Présentation');

        $this->para($section,
            'DMC DataBox est la plateforme officielle de gestion et de partage de documents du Groupe Coris. '.
            'Elle centralise l\'ensemble des ressources documentaires (rapports, visuels, publications, supports audio et vidéo) '.
            'et les met à disposition des collaborateurs selon leur rôle et leur périmètre géographique.'
        );
        $this->para($section,
            'Face aux exigences de sécurité du secteur bancaire, l\'application a été conçue et renforcée pour garantir '.
            'la confidentialité des documents, l\'intégrité des accès et la protection contre les tentatives d\'intrusion.'
        );

        // ── 1 ────────────────────────────────────────────────────
        $this->section($section, '1.  Connexion Chiffrée entre l\'Utilisateur et l\'Application');

        $this->para($section,
            'Toute communication entre l\'utilisateur et l\'application est chiffrée. Personne ne peut intercepter '.
            'ce qui est échangé — ni les mots de passe, ni les documents, ni aucune donnée personnelle.'
        );
        $this->para($section,
            'Si quelqu\'un tente d\'accéder à l\'application via une connexion non sécurisée, il est automatiquement '.
            'redirigé vers la version sécurisée sans aucune intervention de l\'utilisateur.'
        );
        $this->check($section, 'Chiffrement de bout en bout de toutes les communications');
        $this->check($section, 'Redirection automatique vers la connexion sécurisée');
        $this->check($section, 'Le navigateur mémorise la connexion sécurisée pendant 1 an');

        // ── 2 ────────────────────────────────────────────────────
        $this->section($section, '2.  Protection contre les Attaques Courantes');

        $this->para($section,
            'L\'application est protégée contre les formes d\'attaques les plus répandues sur Internet.'
        );
        $this->check($section, 'Usurpation de page — un attaquant ne peut pas intégrer l\'application dans un faux site pour piéger les utilisateurs');
        $this->check($section, 'Injection de contenu malveillant — l\'application n\'accepte que ce qu\'elle connaît et attend');
        $this->check($section, 'Fuite d\'informations — en cas d\'erreur, aucun détail technique n\'est affiché');
        $this->check($section, 'Falsification de requêtes — chaque action est vérifiée et authentifiée');

        // ── 3 ────────────────────────────────────────────────────
        $this->section($section, '3.  Les Documents sont Protégés par une Serrure Invisible');

        $this->para($section,
            'Les fichiers stockés dans l\'application ne sont pas accessibles directement depuis Internet. '.
            'Même si quelqu\'un connaît l\'adresse exacte d\'un document, il obtiendra un accès refusé.'
        );
        $this->para($section,
            'Chaque accès à un fichier passe obligatoirement par l\'application, qui vérifie d\'abord que l\'utilisateur '.
            'est bien connecté et qu\'il dispose du droit de consulter ce document.'
        );
        $this->check($section, 'Aucun fichier accessible sans être connecté');
        $this->check($section, 'Chaque demande de fichier est vérifiée individuellement');
        $this->check($section, 'Les documents ne sont pas stockés dans un espace public');

        // ── 4 ────────────────────────────────────────────────────
        $this->section($section, '4.  Chaque Utilisateur a un Niveau d\'Accès Précis');

        $this->para($section,
            'L\'application fonctionne avec 4 profils distincts. Chaque profil est limité exactement à ce dont il a besoin. '.
            'Un utilisateur ne peut pas accéder aux fonctionnalités d\'un profil supérieur, même en essayant de forcer l\'accès.'
        );

        $section->addTextBreak(1);
        $this->tableau($section, [
            ['Profil',          'Ce qu\'il peut faire'],
            ['Administrateur',  'Tout gérer — utilisateurs, documents, paramètres, statistiques'],
            ['DMC',             'Ajouter, modifier et gérer les documents'],
            ['RMC',             'Consulter et télécharger — uniquement les documents de sa filiale'],
            ['Visiteur',        'Consulter les documents uniquement — aucun téléchargement possible'],
        ]);
        $section->addTextBreak(1);

        $r = $section->addTextRun(['spaceAfter' => 120]);
        $r->addText(
            'Exemple : un RMC de Côte d\'Ivoire ne peut pas voir les documents du Sénégal, '.
            'même en tentant de modifier l\'adresse dans son navigateur.',
            ['name' => 'Calibri', 'size' => 10, 'italic' => true, 'color' => '6B7280']
        );

        // ── 5 ────────────────────────────────────────────────────
        $this->section($section, '5.  Blocage Automatique des Accès Non Autorisés');

        $this->check($section, 'Un compte désactivé par un administrateur est immédiatement bloqué, même si l\'utilisateur est déjà connecté');
        $this->check($section, 'Après 5 tentatives de connexion incorrectes, l\'accès est automatiquement bloqué temporairement');
        $this->check($section, 'Le nombre d\'actions possibles par minute est limité pour éviter tout abus ou tentative automatisée');
        $this->check($section, 'Chaque connexion et déconnexion génère une nouvelle clé de session sécurisée');

        // ── 6 ────────────────────────────────────────────────────
        $this->section($section, '6.  Des Mots de Passe Solides Obligatoires');

        $this->para($section,
            'Tous les utilisateurs sont obligés de choisir un mot de passe fort. '.
            'L\'application refuse automatiquement tout mot de passe qui ne respecte pas les critères suivants.'
        );

        $section->addTextBreak(1);
        $this->tableau($section, [
            ['Critère',                   'Règle'],
            ['Longueur',                  '12 caractères minimum'],
            ['Majuscules et minuscules',  'Au moins une lettre majuscule et une lettre minuscule'],
            ['Chiffre',                   'Au moins un chiffre (0 à 9)'],
            ['Caractère spécial',         'Au moins un symbole (! @ # $ % ...)'],
            ['Vérification mondiale',     'Refusé s\'il a déjà été piraté quelque part dans le monde'],
        ]);
        $section->addTextBreak(1);

        $r = $section->addTextRun(['spaceAfter' => 120]);
        $r->addText(
            'Un indicateur visuel guide l\'utilisateur en temps réel lors de la création ou du changement de son mot de passe.',
            ['name' => 'Calibri', 'size' => 10, 'italic' => true, 'color' => '6B7280']
        );

        // ── 7 ────────────────────────────────────────────────────
        $this->section($section, '7.  Contrôle Strict des Fichiers Déposés');

        $this->para($section,
            'Lorsqu\'un fichier est ajouté dans l\'application, plusieurs vérifications automatiques sont effectuées avant acceptation.'
        );
        $this->check($section, 'Seuls les types de fichiers autorisés sont acceptés (images, vidéos, audio, PDF, documents Office)');
        $this->check($section, 'Le contenu réel du fichier est analysé — renommer un fichier dangereux en .pdf ne suffit pas pour le faire passer');
        $this->check($section, 'La taille des fichiers est limitée pour éviter les abus');
        $this->check($section, 'Les fichiers temporaires sont automatiquement nettoyés en cas d\'erreur');

        // ── 8 ────────────────────────────────────────────────────
        $this->section($section, '8.  Gestion des Comptes Réservée à l\'Administrateur');

        $this->check($section, 'Aucun utilisateur ne peut supprimer son propre compte');
        $this->check($section, 'La création, la modification et la désactivation des comptes sont réservées à l\'administrateur');
        $this->check($section, 'Les invitations sont envoyées par email avec un lien sécurisé à usage unique');
        $this->check($section, 'L\'historique des connexions (date et nombre) est enregistré pour chaque utilisateur');

        // ── BILAN ────────────────────────────────────────────────
        $section->addPageBreak();
        $this->titre($section, 'Bilan des Mesures de Sécurité');

        $this->tableau($section, [
            ['Domaine',                                'Statut'],
            ['Connexion chiffrée',                     'Mis en place'],
            ['Protection contre les attaques',         'Mis en place'],
            ['Stockage sécurisé des fichiers',         'Mis en place'],
            ['Contrôle d\'accès par profil',           'Mis en place'],
            ['Politique de mot de passe renforcée',    'Mis en place'],
            ['Vérification des fichiers déposés',      'Mis en place'],
            ['Limitation des tentatives d\'accès',     'Mis en place'],
            ['Gestion des comptes centralisée',        'Mis en place'],
            ['Blocage des comptes inactifs',           'Mis en place'],
            ['Suppression de compte désactivée',       'Mis en place'],
        ], true);

        $section->addTextBreak(2);

        $r = $section->addTextRun(['alignment' => Jc::CENTER, 'spaceAfter' => 200]);
        $r->addText(
            'L\'application DMC DataBox répond aux exigences de sécurité d\'un environnement bancaire. '.
            'Les documents sont protégés, les accès sont contrôlés, les mots de passe sont solides '.
            'et toute tentative d\'intrusion est bloquée automatiquement.',
            ['name' => 'Calibri', 'size' => 11, 'italic' => true, 'color' => self::BLEU_FONCE]
        );

        // ── PIED DE PAGE ─────────────────────────────────────────
        $footer = $section->addFooter();
        $footer->addPreserveText(
            'DMC DataBox  —  Rapport de Sécurité  —  ' . now()->format('Y') . '              Page %1% / %2%',
            ['name' => 'Calibri', 'size' => 9, 'color' => '9CA3AF'],
            ['alignment' => Jc::CENTER]
        );

        // ── SAUVEGARDE ───────────────────────────────────────────
        $filename = 'DMC_DataBox_Rapport_Securite_' . now()->format('Ymd') . '.docx';
        $path     = storage_path('app/' . $filename);

        IOFactory::createWriter($word, 'Word2007')->save($path);

        $this->info("Rapport généré avec succès !");
        $this->line("Fichier : {$path}");

        return Command::SUCCESS;
    }

    private function titre($section, string $text): void
    {
        $r = $section->addTextRun(['spaceBefore' => 0, 'spaceAfter' => 200]);
        $r->addText($text, [
            'name' => 'Calibri', 'size' => 16, 'bold' => true, 'color' => self::BLEU_FONCE,
        ]);
    }

    private function section($section, string $text): void
    {
        $table = $section->addTable([
            'borderSize' => 0, 'borderColor' => self::BLEU_FONCE,
            'cellMargin' => 100,
        ]);
        $table->addRow(400);
        $cell = $table->addCell(9000, [
            'bgColor'     => self::BLEU_FONCE,
            'borderSize'  => 0,
            'borderColor' => self::BLEU_FONCE,
        ]);
        $r = $cell->addTextRun(['alignment' => Jc::LEFT]);
        $r->addText($text, [
            'name' => 'Calibri', 'size' => 12, 'bold' => true, 'color' => self::BLANC,
        ]);

        $section->addTextBreak(1);
    }

    private function para($section, string $text): void
    {
        $r = $section->addTextRun(['spaceAfter' => 120, 'spaceBefore' => 60]);
        $r->addText($text, ['name' => 'Calibri', 'size' => 11, 'color' => '374151']);
    }

    private function check($section, string $text): void
    {
        $r = $section->addTextRun(['spaceAfter' => 80, 'spaceBefore' => 40]);
        $r->addText('  ✓  ', ['name' => 'Calibri', 'size' => 11, 'bold' => true, 'color' => self::VERT]);
        $r->addText($text,   ['name' => 'Calibri', 'size' => 11, 'color' => '374151']);
    }

    private function tableau($section, array $rows, bool $colorStatus = false): void
    {
        $table = $section->addTable([
            'borderSize'  => 6,
            'borderColor' => self::GRIS_BORD,
            'cellMargin'  => 120,
        ]);

        foreach ($rows as $i => $row) {
            $table->addRow($i === 0 ? 500 : 380);
            foreach ($row as $j => $cell) {
                $isHeader = $i === 0;
                $isStatus = $colorStatus && $j === 1 && !$isHeader;
                $bgColor  = $isHeader ? self::BLEU_FONCE : ($i % 2 === 0 ? self::GRIS_CLAIR : self::BLANC);
                $width    = $j === 0 ? 3800 : 5200;

                $td = $table->addCell($width, ['bgColor' => $bgColor]);
                $tr = $td->addTextRun();
                $tr->addText($cell, [
                    'name'  => 'Calibri',
                    'size'  => 10,
                    'bold'  => $isHeader,
                    'color' => $isHeader ? self::BLANC : ($isStatus ? self::VERT : '374151'),
                ]);
            }
        }
    }
}
