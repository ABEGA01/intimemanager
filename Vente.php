<!-- Formulaire de vente -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Nouvelle Vente</h5>
    </div>
    <div class="card-body">
        <form id="venteForm" action="" method="POST">
            <input type="hidden" name="action" value="nouvelle_vente">
            
            <!-- Sélection du client -->
            <div class="mb-3">
                <label for="client_id" class="form-label">Client</label>
                <select name="client_id" id="client_id" class="form-select" required>
                    <option value="">Sélectionnez un client</option>
                    <?php
                    $clients = $pdo->query("SELECT * FROM client ORDER BY NOM, PRENOM")->fetchAll();
                    foreach ($clients as $client): ?>
                        <option value="<?php echo $client['IDCLIENT']; ?>">
                            <?php echo htmlspecialchars($client['NOM'] . ' ' . $client['PRENOM']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="mt-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#ajoutClientModal">
                        Nouveau Client
                    </button>
                </div>
            </div>

            <!-- Reste du formulaire -->
            // ... existing code ...
        </form>
    </div>
</div>

<!-- Modal Ajout Client Rapide -->
<div class="modal fade" id="ajoutClientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un Nouveau Client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="ajoutClientRapideForm">
                    <div class="mb-3">
                        <label for="nouveau_nom" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="nouveau_nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_prenom" class="form-label">Prénom</label>
                        <input type="text" class="form-control" id="nouveau_prenom" required>
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_telephone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="nouveau_telephone" required>
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="nouveau_email">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" id="btnAjoutClientRapide">Ajouter</button>
            </div>
        </div>
    </div>
</div>

<script>
// Gestion de l'ajout rapide de client
document.getElementById('btnAjoutClientRapide').addEventListener('click', function() {
    var nom = document.getElementById('nouveau_nom').value;
    var prenom = document.getElementById('nouveau_prenom').value;
    var telephone = document.getElementById('nouveau_telephone').value;
    var email = document.getElementById('nouveau_email').value;

    // Appel AJAX pour ajouter le client
    fetch('ajax/ajouter_client.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            nom: nom,
            prenom: prenom,
            telephone: telephone,
            email: email
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Ajout de l'option dans le select
            var select = document.getElementById('client_id');
            var option = new Option(nom + ' ' + prenom, data.client_id);
            select.add(option);
            select.value = data.client_id;

            // Fermeture du modal
            var modal = bootstrap.Modal.getInstance(document.getElementById('ajoutClientModal'));
            modal.hide();

            // Message de succès
            alert('Client ajouté avec succès');
        } else {
            alert('Erreur lors de l\'ajout du client: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur lors de l\'ajout du client');
    });
});
</script> 