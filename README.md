# 🚀 Taramoney PHP SDK (Non Officiel)

Un SDK PHP simple, élégant et pensé pour la *Developer Experience* (DX) afin d'intégrer facilement l'API de paiement **Taramoney** dans vos projets PHP, Laravel ou Symfony.

Ce package a été conçu pour masquer les complexités de l'API originale et vous faire gagner du temps.

## ✨ Pourquoi utiliser ce SDK ?

Si vous avez déjà utilisé l'API Taramoney, vous connaissez ces "irritants". Ce SDK les gère automatiquement pour vous :
- **Unicité des Product IDs :** Plus besoin de générer des `productId` uniques à chaque requête, le SDK le gère en arrière-plan.
- **Formatage des numéros de téléphone :** Que l'utilisateur tape `+237 655 251 245`, `655 25 12 45` ou `655251245`, le SDK nettoie tout et ajoute l'indicatif du pays automatiquement.
- **Gestion simplifiée des Webhooks :** Une méthode prête à l'emploi pour décoder les notifications de paiement.

## 📦 Installation

Vous pouvez installer ce package via [Composer](https://getcomposer.org/). 

```bash
composer require consolidis/tara-payment

(Note : Assurez-vous d'avoir PHP 8.0+ et l'extension JSON activée).
🛠️ Configuration initiale
Pour commencer, instanciez le client avec vos clés API (disponibles sur votre tableau de bord Taramoney).

use TaraPayment\TaraClient;

// Initialisation simple
$tara = new TaraClient(
    'VOTRE_API_KEY', 
    'VOTRE_BUSINESS_ID', 
    'https://votre-site.com/api/webhook/tara', // URL de webhook par défaut
    '237' // Indicatif pays par défaut (Ex: Cameroun)
);

💳 Lancer un paiement (Mobile Money)
Rien de plus simple. Passez le nom du produit, le prix et le numéro du client. Le SDK s'occupe du reste.

try {
    $resultat = $tara->initPayment([
        'productName' => 'Abonnement Premium 1 Mois',
        'price'       => 2000,
        'phoneNumber' => '655251245', // Le SDK ajoutera automatiquement le 237 !
        // 'network'  => 'wave' // Optionnel : Décommentez pour le Sénégal/Côte d'Ivoire
    ]);

    // Retourne l'objet contenant le code USSD à composer ou l'URL de redirection
    echo "Paiement initié ! Veuillez taper ce code : " . $resultat['message'];

} catch (\Exception $e) {
    echo "Erreur lors du paiement : " . $e->getMessage();
}

🔄 Gérer les Webhooks (Notifications de paiement)
Lorsque l'utilisateur valide le paiement sur son téléphone, Taramoney envoie une requête POST (Webhook) à votre serveur pour vous informer du succès ou de l'échec.
Voici comment lire cette réponse de manière sécurisée :

// Récupération du JSON brut envoyé par Taramoney
$payload = file_get_contents('php://input');

try {
    $donneesWebhook = $tara->parseWebhook($payload);

    if ($donneesWebhook['status'] === 'SUCCESS') {
        $numero = $donneesWebhook['phoneNumber'];
        // ✅ Paiement réussi : Mettez à jour votre base de données, livrez le produit...
        error_log("Paiement validé pour le numéro : $numero");
    } else {
        // ❌ Le paiement a échoué
        error_log("Échec du paiement.");
    }

} catch (\Exception $e) {
    error_log("Erreur Webhook : " . $e->getMessage());
}

⚠️ Conseil CRUCIAL pour les Webhooks (Éviter les Timeouts)
L'API Taramoney exige que votre serveur réponde rapidement à la requête de Webhook. Si votre code (envoi d'email, mise à jour de la BDD) prend trop de temps, Taramoney considèrera que le Webhook a échoué et ne réessaiera pas.
Pour éviter cela, répondez toujours 200 OK avant d'exécuter votre logique lourde.
Exemple en PHP pur :

if (function_exists('fastcgi_finish_request')) {
    echo json_encode(['status' => 'OK']);
    fastcgi_finish_request(); // Ferme la connexion avec Taramoney immédiatement
}
// Mettez votre code de base de données ici...

Exemple sur Laravel :
Utilisez le système de Queues (Jobs) de Laravel.

public function handleWebhook(Request $request)
{
    $donnees = $tara->parseWebhook($request->getContent());
    
    // On envoie le traitement en arrière-plan
    ProcessPaymentJob::dispatch($donnees); 
    
    // On répond tout de suite "Tout va bien" à Taramoney
    return response()->json(['status' => 'OK'], 200);
}

🤝 Contribution
Les Pull Requests sont les bienvenues ! Si vous trouvez un bug ou souhaitez ajouter une fonctionnalité (comme l'intégration de nouvelles méthodes de l'API), n'hésitez pas à ouvrir une issue ou une PR.
📄 Licence
Ce projet est sous licence MIT. N'hésitez pas à l'utiliser et à le modifier pour vos projets personnels comme commerciaux.