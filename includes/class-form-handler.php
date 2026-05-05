<?php
/**
 * Form Handler - Renders consultation form
 */

if (!defined('ABSPATH')) {
    exit;
}

class Verso_Form_Handler {
    /**
     * Render consultation form shortcode
     *
     * @return string HTML form
     */
    public static function render_form() {
        ob_start();
        ?>
        <div class="verso-consultation-container">
            <div class="verso-consultation-wrapper">
                <div class="verso-consultation-header">
                    <h1 class="verso-consultation-title">
                        🏥 Demande de Consultation
                    </h1>
                    <p class="verso-consultation-subtitle">
                        Troubles locomoteurs, imagerie, chirurgie. Nos équipes vous répondront sous 48 heures.
                    </p>
                </div>

                <form id="verso-consultation-form" enctype="multipart/form-data" class="verso-form" novalidate>
                    <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('verso_consultation')); ?>" />
                    <input type="hidden" name="uuid" value="<?php echo esc_attr(wp_generate_uuid4()); ?>" />
                    <input type="hidden" name="specialite" value="Troubles Locomoteurs" />

                    <!-- Hidden field: always owner (removed choice) -->
                    <input type="hidden" name="submitter_type" value="owner" />

                    <!-- Veterinarian Section (always visible, optional) -->
                    <div id="vet-section" class="verso-form-section">
                        <h3 class="verso-section-title">🏥 Vétérinaire Référant (Optionnel)</h3>
                        <p class="verso-form-help">Si la demande est guidée par un vétérinaire, remplissez ses coordonnées ci-dessous</p>

                        <div class="verso-form-row">
                            <div class="verso-form-col verso-form-col-6">
                                <div class="verso-form-group">
                                    <label for="vet_nom" class="verso-form-label">Nom *</label>
                                    <input type="text" class="verso-form-control" id="vet_nom" name="vet_nom" />
                                </div>
                            </div>
                            <div class="verso-form-col verso-form-col-6">
                                <div class="verso-form-group">
                                    <label for="vet_prenom" class="verso-form-label">Prénom</label>
                                    <input type="text" class="verso-form-control" id="vet_prenom" name="vet_prenom" />
                                </div>
                            </div>
                        </div>

                        <div class="verso-form-group">
                            <label for="vet_clinique" class="verso-form-label">Clinique Vétérinaire *</label>
                            <input type="text" class="verso-form-control" id="vet_clinique" name="vet_clinique" placeholder="Nom de votre clinique" />
                        </div>

                        <div class="verso-form-row">
                            <div class="verso-form-col verso-form-col-6">
                                <div class="verso-form-group">
                                    <label for="vet_email" class="verso-form-label">Email Pro *</label>
                                    <input type="email" class="verso-form-control" id="vet_email" name="vet_email" />
                                </div>
                            </div>
                            <div class="verso-form-col verso-form-col-6">
                                <div class="verso-form-group">
                                    <label for="vet_telephone" class="verso-form-label">Téléphone *</label>
                                    <input type="tel" class="verso-form-control" id="vet_telephone" name="vet_telephone" placeholder="+33..." />
                                </div>
                            </div>
                        </div>

                        <div class="verso-form-group">
                            <label for="vet_adresse" class="verso-form-label">Adresse (optionnel)</label>
                            <input type="text" class="verso-form-control" id="vet_adresse" name="vet_adresse" />
                        </div>
                    </div>

                    <!-- Owner Section (always visible) -->
                    <div id="owner-section" class="verso-form-section">
                        <h3 class="verso-section-title">👤 Coordonnées du Propriétaire/Contact</h3>

                        <div class="verso-form-row">
                            <div class="verso-form-col verso-form-col-6">
                                <div class="verso-form-group">
                                    <label for="owner_nom" class="verso-form-label">Nom *</label>
                                    <input type="text" class="verso-form-control" id="owner_nom" name="owner_nom" />
                                </div>
                            </div>
                            <div class="verso-form-col verso-form-col-6">
                                <div class="verso-form-group">
                                    <label for="owner_prenom" class="verso-form-label">Prénom *</label>
                                    <input type="text" class="verso-form-control" id="owner_prenom" name="owner_prenom" />
                                </div>
                            </div>
                        </div>

                        <div class="verso-form-row">
                            <div class="verso-form-col verso-form-col-6">
                                <div class="verso-form-group">
                                    <label for="owner_email" class="verso-form-label">Email *</label>
                                    <input type="email" class="verso-form-control" id="owner_email" name="owner_email" required />
                                </div>
                            </div>
                            <div class="verso-form-col verso-form-col-6">
                                <div class="verso-form-group">
                                    <label for="owner_telephone" class="verso-form-label">Téléphone *</label>
                                    <input type="tel" class="verso-form-control" id="owner_telephone" name="owner_telephone" placeholder="+33..." required />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Animal Section -->
                    <div class="verso-form-section">
                        <h3 class="verso-section-title">🐾 Patient Animal</h3>

                        <div class="verso-form-group">
                            <label for="animal_nom" class="verso-form-label">Nom du Patient *</label>
                            <input type="text" class="verso-form-control" id="animal_nom" name="animal_nom" placeholder="Rex, Minou, etc." required />
                        </div>

                        <div class="verso-form-row">
                            <div class="verso-form-col verso-form-col-6">
                                <div class="verso-form-group">
                                    <label for="animal_espece" class="verso-form-label">Espèce *</label>
                                    <select class="verso-form-control verso-form-select" id="animal_espece" name="animal_espece" required>
                                        <option value="">-- Sélectionnez --</option>
                                        <option value="Chien">🐕 Chien</option>
                                        <option value="Chat">🐈 Chat</option>
                                        <option value="Lapin">🐰 Lapin</option>
                                        <option value="NAC">🦗 NAC (Petit animal)</option>
                                        <option value="Cheval">🐴 Cheval</option>
                                        <option value="Autre">📋 Autre</option>
                                    </select>
                                </div>
                            </div>
                            <div class="verso-form-col verso-form-col-6">
                                <div class="verso-form-group">
                                    <label for="animal_race" class="verso-form-label">Race</label>
                                    <input type="text" class="verso-form-control" id="animal_race" name="animal_race" placeholder="Ex: Golden Retriever, Persan, etc." />
                                </div>
                            </div>
                        </div>

                        <div class="verso-form-row">
                            <div class="verso-form-col verso-form-col-4">
                                <div class="verso-form-group">
                                    <label for="animal_sexe" class="verso-form-label">Sexe</label>
                                    <select class="verso-form-control verso-form-select" id="animal_sexe" name="animal_sexe">
                                        <option value="">-- Non spécifié --</option>
                                        <option value="M">Mâle</option>
                                        <option value="F">Femelle</option>
                                        <option value="Castré">Castré</option>
                                        <option value="Stérilisée">Stérilisée</option>
                                    </select>
                                </div>
                            </div>
                            <div class="verso-form-col verso-form-col-4">
                                <div class="verso-form-group">
                                    <label for="animal_date_naissance" class="verso-form-label">Naissance</label>
                                    <input type="date" class="verso-form-control" id="animal_date_naissance" name="animal_date_naissance" />
                                </div>
                            </div>
                            <div class="verso-form-col verso-form-col-4">
                                <div class="verso-form-group">
                                    <label for="animal_poids" class="verso-form-label">Poids (kg)</label>
                                    <input type="number" class="verso-form-control" id="animal_poids" name="animal_poids" step="0.1" />
                                </div>
                            </div>
                        </div>

                        <div class="verso-form-group">
                            <label for="animal_puce" class="verso-form-label">N° Puce Électronique</label>
                            <input type="text" class="verso-form-control" id="animal_puce" name="animal_puce" />
                        </div>
                    </div>

                    <!-- Consultation Details Section -->
                    <div class="verso-form-section">
                        <h3 class="verso-section-title">📋 Description du Cas</h3>

                        <div class="verso-form-group">
                            <label for="motif" class="verso-form-label">Motif de la Consultation *</label>
                            <textarea class="verso-form-control verso-form-textarea" id="motif" name="motif" rows="4" placeholder="Décrivez en détail la raison de la consultation..." required></textarea>
                        </div>

                        <div class="verso-form-group">
                            <label for="traitements" class="verso-form-label">Traitements en Cours</label>
                            <textarea class="verso-form-control verso-form-textarea" id="traitements" name="traitements" rows="2" placeholder="Listez les traitements actuels..."></textarea>
                        </div>
                    </div>

                    <!-- Documents Section -->
                    <div class="verso-form-section">
                        <h3 class="verso-section-title">📎 Documents Médicaux (optionnel)</h3>

                        <p class="verso-form-help">
                            Formats acceptés: PDF, JPG, PNG, TIFF, DICOM — Max 50 MB
                        </p>

                        <div class="verso-form-group">
                            <label for="fichiers" class="verso-form-label">Sélectionnez vos fichiers</label>
                            <input type="file" class="verso-form-control verso-file-input" id="fichiers" name="fichiers[]" multiple accept=".pdf,.jpg,.jpeg,.png,.tiff,.dcm" />
                            <small class="verso-form-hint">Vous pouvez sélectionner plusieurs fichiers</small>
                        </div>

                        <div id="file-preview" class="verso-file-preview"></div>
                    </div>

                    <!-- Submit -->
                    <div class="verso-form-actions">
                        <button type="submit" class="verso-btn verso-btn-primary">
                            📤 Envoyer la Demande
                        </button>
                    </div>

                    <div id="form-message" class="verso-message"></div>
                </form>

                <!-- Contact Alternative -->
                <div class="verso-contact-info">
                    <p><strong>📧 Contactez-nous directement:</strong></p>
                    <p><a href="mailto:consultations@verso-vet.com">consultations@verso-vet.com</a></p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
