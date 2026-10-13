<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Décision N° {{ $reference_decision }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 12mm 8mm 12mm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8.5pt;
            color: #000000;
            line-height: 1.15;
            margin: 0;
            padding: 0;
        }

        /* En-tête national supérieur centré avec espace sous le logo République */
        .republique-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo-republique {
            width: 100px;
            height: auto;
            display: block;
            margin: 0 auto 4px auto;
        }

        .republique-title {
            font-weight: bold;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .republique-devise {
            font-size: 7.5pt;
            font-style: italic;
            margin-top: 1px;
        }

        /* Tableau principal de l'en-tête */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .header-table td {
            vertical-align: top;
        }

        .left-header {
            width: 38%;
            text-align: center;
            /* Centré pour aligner le logo et les textes de la région */
        }

        .right-header {
            width: 62%;
            text-align: left;
        }

        /* Logo et titres Région */
        .logo-region {
            width: 65px;
            height: auto;
            display: block;
            margin: 0 auto 4px auto;
        }

        .region-title {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 8px;
        }

        .sec-gen {
            font-size: 7pt;
            text-align: center;
            line-height: 1.1;
        }

        .separator-line {
            text-align: center;
            font-size: 6pt;
            letter-spacing: 1.5px;
            margin: 2px 0;
        }

        /* Cadre de la Décision */
        .decision-box {
            border: 1px solid #000;
            padding: 6px 8px;
        }

        .decision-title {
            font-weight: bold;
            font-size: 8.5pt;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .decision-objet {
            font-size: 8pt;
            text-align: justify;
            margin-top: 2px;
        }

        .agent-info {
            margin-top: 4px;
            font-weight: bold;
            font-size: 8pt;
            border-top: 1px dotted #000;
            padding-top: 4px;
        }

        /* Tableau Comparatif Positions */
        .positions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            table-layout: fixed;
        }

        .positions-table td {
            border: 1px solid #000;
            width: 50%;
            vertical-align: top;
            padding: 6px 8px;
        }

        .pos-header {
            text-align: center;
            font-weight: bold;
            font-size: 8pt;
            text-transform: uppercase;
            margin-bottom: 5px;
            text-decoration: underline;
        }

        .pos-content {
            font-size: 7.5pt;
            line-height: 1.2;
        }

        .pos-content p {
            margin: 2px 0;
        }

        /* Pied de page et Signatures */
        .footer-section {
            margin-top: 25px;
            width: 100%;
            page-break-inside: avoid;
        }

        .date-lieu {
            text-align: center;
            font-size: 8pt;
            margin-bottom: 10px;
            margin-left: 50%;
        }
    </style>
</head>

<body>

    <!-- EN-TÊTE RÉPUBLIQUE DE MADAGASCAR AVEC LOGO -->
    <div class="republique-header">
        <img src="{{ public_path('logomada.jpg') }}" class="logo-republique" alt="">
        <div class="republique-title">REPOBLIKAN'I MADAGASIKARA</div>
        <div class="republique-devise">Fitiavana - Tanindrazana - Fandrosoana</div>
    </div>

    <!-- TABLEAU PRINCIPAL EN-TÊTE -->
    <table class="header-table">
        <tr>
            <td class="left-header">
                <!-- LOGO RÉGION PLACÉ AU-DESSUS DU NOM -->
                <img src="{{ public_path('logo.png') }}" class="logo-region" alt="Logo Région Haute Matsiatra">
                <div class="region-title">REGION HAUTE<br>MATSIATRA</div>
                <div class="sec-gen">
                    SECRETARIAT GENERAL<br>
                    <div class="separator-line">*****</div>
                    DIRECTION DES AFFAIRES GENERALES<br>
                    <div class="separator-line">*****</div>
                    SERVICE DES RESSOURCES HUMAINES
                </div>
            </td>
            <td class="right-header">
                <!-- CADRE DE LA DÉCISION -->
                <div class="decision-box">
                    <div class="decision-title">
                        DECISION N° {{ $reference_decision }} REG/HM
                    </div>
                    <div class="decision-objet">
                        Portant octroi d'un congé annuel de TRENTE *3(30*3) jours, avec solde entière au titre des années {{ $annee_debut }}-{{ $annee_fin }}
                    </div>
                    <div class="agent-info">
                        NOM ET PRENOMS : {{ $nom_prenom }}<br>
                        IM : {{ $im }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- TABLEAU COMPARATIF : ANCIENNE POSITION / NOUVELLE POSITION -->
    <table class="positions-table">
        <tr>
            <td>
                <div class="pos-header">= ANCIENNE POSITION =</div>
                <div class="pos-content">
                    <p><b>BUDGET :</b> Région</p>
                    <p><b>IMPUTATION BUDGETAIRE :</b> {{ $imputation_budgetaire }}</p>
                    <p><b>GRADE OU EMPLOI :</b> {{ $grade_emploi }}</p>
                    <p style="margin-top: 6px;">En service au bureau de la Région Haute Matsiatra</p>
                </div>
            </td>
            <td>
                <div class="pos-header">= NOUVELLE POSITION =</div>
                <div class="pos-content">
                    <p style="text-align: center; font-weight: bold; margin-bottom: 4px;">- INCHANGEE -</p>
                    <p style="text-align: justify;">
                        Obtient un congé annuel de TRENTE *3(30*3) jours avec solde entière au titre des années {{ $annee_debut }}-{{ $annee_fin }}
                    </p>
                    <p style="margin-top: 6px; text-align: justify;">
                        à compter de la date de son départ pour en jouir à Fianarantsoa.
                    </p>
                </div>
            </td>
        </tr>
    </table>

    <!-- PIED DE PAGE -->
    <div class="footer-section">
        <div class="date-lieu">
            Fianarantsoa, le {{ $date_decision ?? now()->format('d/m/Y') }}
        </div>
    </div>

</body>

</html>