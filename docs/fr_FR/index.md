Description 
===

Plugin pour piloter un client Deluge Torrent [https://deluge-torrent.org/](https://deluge-torrent.org/)

![Icone](../img/deluge_icon.png)

Dépendance
===

Ce plugin ce base sur un plugin dans Deluge pour fonctionner. Il faut donc que ce dernier soit correctement install [Voir ici](https://github.com/idlesign/deluge-webapi#installation)

![Mini procédure d'install WebAPI](../img/install%20WebAPI.png)

L'installation est très simple si vous avez déjà le WebUI. Sinon, commencez par installer le WebUI.

Aussi, pensez à bien activer le plugin.

![Activer plugin dans jeedom](../img/ActivePlugin.png)

Création d'un Équipement Deluge
===

Aller dans :

![menu->Plugins->Multimédia->Deluge Torrent](../img/Menu.png)

Cliquer sur "Ajouter" :

![Ajouter](../img/Ajouter.png)

Choisir un Nom pour votre client Torrent:

Renseignez l'IP ou l'URL de votre DelugeWeb et le port. Le Port par defaut de delugeWeb est 8112.

Le mot de passe de votre DelugeWeb.

Et la fréquence de rafraîchissement des commandes.

N'oubliez pas d'Activer l'équipement et de le rendre Visible. Eventuellement de lui choisi un Catégorie et un "Objet parent"

![Configure](../img/Configure.png)

Puis cliquez sûr l'onglé "Commande"

Arrangez l'ordre des commandes Jeedom avec des Drag & drop:

![Drag&Drop](../img/SetArrangement.png)

Je vous propose l'ordre suivant (Mais libre à vous de choisir):

![Arrangement Fini](../img/ArrangementFini.png)

Puis configurez les Min et Max en fonction des capacités de votre connexion internet (à défaut Jeedom à 0 mini 100 maxi)

![Set min Max](../img/SetMinMax.png)

NE PAS OUBLIER DE SAUVEGARDER

Redimensionner le Widget
===

Si vous avais bien configurer (activer l'l'équipement et mis Visible et Choisie un catégorie). Vous devriez avoir ceci sûr le Dashboard :

![widget Default](../img/widget%20Default.png)

Cliquez sur le crayon en haut a droit pour le redimensionner:

![Crayon Resize](../img/Resize.png)

À fin d'obtenir ceci :

![Widget resized](../img/widget%20Resized.png)

Re cliquez sur le crayon en haut a droit pour sauvegarder votre redimensionnement:

![Crayon Resize](../img/Resize.png)

Voila c'est fini !!
