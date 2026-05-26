<script setup>
import { nextTick, reactive, ref } from 'vue';
import { useAuth } from '../composables/useAuth.js';
import { useModal } from '../composables/useModal.js';
import { navigateTo } from '../composables/usePortalNavigation.js';
import { useToast } from '../composables/useToast.js';
import { PalabrasWeb } from '../PalabrasWeb.js';

const words = PalabrasWeb.auth;
const backgroundImage = new URL('../../../stitch_inversiones_s_m_future_b2b_portal/stitch_inversiones_s_m_future_b2b_portal/cinematic_wide_shot_of_a_futuristic_luxury_electric_sports_car_interior_and/screen.png', import.meta.url).href;
const { login } = useAuth();
const { openModal } = useModal();
const { error, success } = useToast();
const loading = ref(false);
const passwordFlowOpen = ref(false);
const checkingPasswordIdentity = ref(false);
const creatingPassword = ref(false);
const showLoginPassword = ref(true);
const showPasswordText = ref(false);
const desktopPasswordInput = ref(null);
const mobilePasswordInput = ref(null);
const passwordStep = ref('identity');
const passwordMessage = ref('');
const passwordClient = ref(null);

const form = reactive({
    identity: '',
    password: '',
});

const mobileForm = reactive({
    identity: '',
    password: '',
});

const errors = reactive({
    identity: '',
    mobileIdentity: '',
    loginPassword: '',
    mobileLoginPassword: '',
    createIdentity: '',
    newPassword: '',
    passwordConfirmation: '',
});

const passwordForm = reactive({
    identity: '',
    password: '',
    password_confirmation: '',
});

const validate = (values, mode = 'desktop') => {
    const identityKey = mode === 'mobile' ? 'mobileIdentity' : 'identity';
    const passwordKey = mode === 'mobile' ? 'mobileLoginPassword' : 'loginPassword';

    errors[identityKey] = values.identity.trim() ? '' : 'Ingrese su número de identidad o RTN.';
    errors[passwordKey] = '';

    return !errors[identityKey];
};

const focusPasswordInput = (mode = 'desktop') => {
    nextTick(() => {
        const input = mode === 'mobile' ? mobilePasswordInput.value : desktopPasswordInput.value;
        input?.focus?.();
    });
};

const handleLogin = async (mode = 'desktop') => {
    const values = mode === 'mobile' ? mobileForm : form;

    if (!validate(values, mode)) {
        error('Ingresa tu número de identidad o RTN para continuar.');
        return;
    }

    loading.value = true;

    try {
        const result = await login({ identity: values.identity.trim(), password: values.password });

        if (!result.authenticated) {
            if (result.requiresPassword) {
                showLoginPassword.value = true;
                const passwordKey = mode === 'mobile' ? 'mobileLoginPassword' : 'loginPassword';
                errors[passwordKey] = result.message === 'Identidad o contrasena incorrecta.'
                    ? 'Identidad o contraseña incorrecta.'
                    : 'Este cliente ya tiene contraseña. Ingrésela para continuar.';
                focusPasswordInput(mode);
                error(errors[passwordKey]);
                return;
            }

            error(result.message || 'No encontramos un cliente B2B con ese número de identidad o RTN.');
            return;
        }

        success('Sesión temporal B2B iniciada.');
        navigateTo('/panel');
    } catch {
        error('No se pudo validar la identidad en este momento.');
    } finally {
        loading.value = false;
    }
};

const postPortalAuth = async (endpoint, payload) => {
    const response = await window.fetch(endpoint, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
    });
    const contentType = response.headers.get('Content-Type') || '';
    const data = contentType.includes('application/json') ? await response.json() : null;

    return { ok: response.ok, status: response.status, data };
};

const togglePasswordFlow = () => {
    passwordFlowOpen.value = !passwordFlowOpen.value;
    if (passwordFlowOpen.value && !passwordForm.identity.trim()) {
        passwordForm.identity = form.identity.trim() || mobileForm.identity.trim();
    }
};

const resetPasswordFlow = () => {
    passwordStep.value = 'identity';
    passwordMessage.value = '';
    passwordClient.value = null;
    passwordForm.password = '';
    passwordForm.password_confirmation = '';
    errors.createIdentity = '';
    errors.newPassword = '';
    errors.passwordConfirmation = '';
};

const checkPasswordIdentity = async () => {
    resetPasswordFlow();
    errors.createIdentity = passwordForm.identity.trim() ? '' : 'Ingresa tu identidad o RTN.';

    if (errors.createIdentity) {
        error(errors.createIdentity);
        return;
    }

    checkingPasswordIdentity.value = true;

    try {
        const result = await postPortalAuth('/api/portal/auth/check-identity', { identity: passwordForm.identity.trim() });
        const data = result.data?.data || {};

        if (!data.exists) {
            passwordMessage.value = 'No encontramos un cliente B2B con ese número de identidad o RTN.';
            error(passwordMessage.value);
            return;
        }

        passwordClient.value = data.client || null;

        if (data.hasPassword) {
            passwordMessage.value = 'Este cliente ya tiene contraseña creada. Inicie sesión.';
            showLoginPassword.value = true;
            form.identity = passwordForm.identity.trim();
            mobileForm.identity = passwordForm.identity.trim();
            focusPasswordInput();
            passwordStep.value = 'blocked';
            return;
        }

        if (!data.canCreatePassword) {
            passwordMessage.value = 'No se puede crear contraseña para esta identidad en este momento.';
            passwordStep.value = 'blocked';
            return;
        }

        passwordMessage.value = 'Identidad validada. Cree una contraseña para iniciar sesión en el portal.';
        passwordStep.value = 'password';
    } catch {
        error('No se pudo verificar la identidad en este momento.');
    } finally {
        checkingPasswordIdentity.value = false;
    }
};

const validatePasswordCreation = () => {
    errors.newPassword = passwordForm.password.length >= 8 ? '' : 'Mínimo 8 caracteres.';
    errors.passwordConfirmation = passwordForm.password_confirmation === passwordForm.password ? '' : 'La confirmación no coincide.';

    if (passwordForm.password && (!/[A-Za-z]/.test(passwordForm.password) || !/[0-9]/.test(passwordForm.password))) {
        errors.newPassword = 'Debe incluir al menos una letra y un número.';
    }

    return !errors.newPassword && !errors.passwordConfirmation;
};

const submitPasswordCreation = async () => {
    if (!validatePasswordCreation()) {
        error('Revise los requisitos de contraseña.');
        return;
    }

    creatingPassword.value = true;

    try {
        const result = await postPortalAuth('/api/portal/auth/create-password', {
            identity: passwordForm.identity.trim(),
            password: passwordForm.password,
            password_confirmation: passwordForm.password_confirmation,
        });
        const data = result.data?.data || {};

        if (!result.ok || !data.created) {
            passwordMessage.value = data.message || 'No se pudo crear la contraseña.';
            error(passwordMessage.value);
            return;
        }

        success(data.message || 'Contraseña creada correctamente.');
        passwordMessage.value = 'Contraseña creada correctamente. Ahora puede iniciar sesión.';
        passwordStep.value = 'success';
        form.identity = passwordForm.identity.trim();
        mobileForm.identity = passwordForm.identity.trim();
        showLoginPassword.value = true;
        passwordFlowOpen.value = false;
        focusPasswordInput();
        passwordForm.password = '';
        passwordForm.password_confirmation = '';
    } catch {
        error('No se pudo crear la contraseña en este momento.');
    } finally {
        creatingPassword.value = false;
    }
};

const openHelp = (type) => {
    const messages = {
        forgot: 'Ingresa tu correo corporativo y un asesor B2B validará el restablecimiento de acceso.',
        access: 'Tu solicitud de acceso quedará registrada para revisión comercial.',
        support: 'Soporte B2B registrará tu consulta de acceso y te contactará por los canales asociados a tu cuenta.',
        privacy: 'La política de privacidad final debe conectarse al documento legal aprobado. Esta vista no almacena credenciales reales.',
        terms: 'Los términos finales del portal B2B quedan pendientes de validación legal y comercial.',
        language: 'El portal mantiene idioma español para esta versión funcional.',
        biometric: 'El acceso biométrico requiere integración con autenticación real. En esta fase usa ingreso temporal por identidad o RTN.',
    };

    openModal({
        title: type === 'forgot' ? 'Recuperar contraseña' : type === 'access' ? 'Solicitar acceso' : type === 'language' ? 'Idioma del portal' : type === 'privacy' ? 'Privacidad' : type === 'terms' ? 'Términos' : type === 'biometric' ? 'Acceso biométrico' : 'Soporte de acceso',
        message: messages[type] || messages.support,
        icon: type === 'language' ? 'language' : type === 'biometric' ? 'fingerprint' : 'support_agent',
        confirmText: 'Registrar',
        cancelText: 'Cerrar',
        onConfirm: () => success('Solicitud registrada correctamente.'),
    });
};
</script>

<template>
    <main class="stitch-login" aria-labelledby="login-title">
        <div class="login-bg" aria-hidden="true">
            <img :alt="words.backgroundAlt" :src="backgroundImage">
            <div class="desktop-bg-gradient"></div>
            <div class="mobile-bg-gradient-top"></div>
            <div class="mobile-bg-gradient-radial"></div>
            <div class="desktop-particles">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>

        <section class="login-desktop-shell">
            <header class="desktop-brand">
                <h1 id="login-title">{{ words.brandName }}</h1>
                <p>{{ words.desktop.portalName }}</p>
            </header>

            <form class="desktop-card" @submit.prevent="handleLogin('desktop')">
                <div class="card-top-glow" aria-hidden="true"></div>
                <header class="desktop-card-header">
                    <h2>{{ words.desktop.accessTitle }}</h2>
                    <span></span>
                </header>

                <div class="desktop-form-stack">
                    <label class="desktop-field">
                        <span>{{ words.desktop.fields.username.label }}</span>
                        <span class="desktop-input liquid-glass input-glow">
                            <span class="material-symbols-outlined">person</span>
                            <input v-model="form.identity" type="text" :placeholder="words.desktop.fields.username.placeholder" autocomplete="off" inputmode="numeric">
                        </span>
                        <small v-if="errors.identity" class="login-error">{{ errors.identity }}</small>
                    </label>

                    <label v-if="showLoginPassword" class="desktop-field">
                        <span>Contraseña</span>
                        <span class="desktop-input liquid-glass input-glow">
                            <span class="material-symbols-outlined">lock</span>
                            <input ref="desktopPasswordInput" v-model="form.password" :type="showPasswordText ? 'text' : 'password'" placeholder="Ingrese su contraseña" autocomplete="current-password">
                            <button class="password-visibility" type="button" :aria-label="showPasswordText ? 'Ocultar contraseña' : 'Mostrar contraseña'" @click="showPasswordText = !showPasswordText">
                                <span class="material-symbols-outlined">{{ showPasswordText ? 'visibility_off' : 'visibility' }}</span>
                            </button>
                        </span>
                        <small v-if="errors.loginPassword" class="login-error">{{ errors.loginPassword }}</small>
                    </label>

                    <p class="login-temp-note">Ingrese con su identidad o RTN. Si ya creó una contraseña, colóquela para acceder.</p>

                    <section class="password-create-box">
                        <button type="button" class="password-create-toggle" @click="togglePasswordFlow">
                            <span class="material-symbols-outlined">lock_reset</span>
                            <span>Crear contraseña</span>
                        </button>

                        <div v-if="passwordFlowOpen" class="password-create-panel">
                            <p class="password-security-note">Cree una contraseña para su cliente B2B registrado por identidad/RTN.</p>

                            <label class="password-field">
                                <span>Identidad o RTN</span>
                                <input v-model="passwordForm.identity" type="text" inputmode="numeric" autocomplete="off" placeholder="0801**********">
                                <small v-if="errors.createIdentity" class="login-error">{{ errors.createIdentity }}</small>
                            </label>

                            <button type="button" class="password-action" :disabled="checkingPasswordIdentity" @click="checkPasswordIdentity">
                                {{ checkingPasswordIdentity ? 'Verificando...' : 'Verificar identidad' }}
                            </button>

                            <div v-if="passwordClient" class="password-client-card">
                                <strong>{{ passwordClient.name }}</strong>
                                <span>{{ passwordClient.code }} · {{ passwordClient.vatNumberMasked }}</span>
                            </div>

                            <p v-if="passwordMessage" class="password-flow-message">{{ passwordMessage }}</p>

                            <div v-if="passwordStep === 'password'" class="password-fields-stack">
                                <label class="password-field">
                                    <span>Nueva contraseña</span>
                                    <input v-model="passwordForm.password" type="password" autocomplete="new-password" placeholder="Mínimo 8 caracteres">
                                    <small v-if="errors.newPassword" class="login-error">{{ errors.newPassword }}</small>
                                </label>

                                <label class="password-field">
                                    <span>Confirmar contraseña</span>
                                    <input v-model="passwordForm.password_confirmation" type="password" autocomplete="new-password" placeholder="Repita la contraseña">
                                    <small v-if="errors.passwordConfirmation" class="login-error">{{ errors.passwordConfirmation }}</small>
                                </label>

                                <ul class="password-rules">
                                    <li>Mínimo 8 caracteres</li>
                                    <li>Al menos una letra</li>
                                    <li>Al menos un número</li>
                                </ul>

                                <button type="button" class="password-action password-action--primary" :disabled="creatingPassword" @click="submitPasswordCreation">
                                    {{ creatingPassword ? 'Creando...' : 'Crear contraseña' }}
                                </button>
                            </div>
                        </div>
                    </section>

                    <label class="desktop-remember">
                        <span class="checkbox-wrap">
                            <input id="remember-desktop" type="checkbox">
                            <span class="material-symbols-outlined">check</span>
                        </span>
                        <span>{{ words.desktop.remember }}</span>
                    </label>

                    <button class="desktop-submit" type="submit" :disabled="loading">
                        <span class="submit-inner">
                            <span>{{ loading ? 'Validando...' : words.desktop.submit }}</span>
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </span>
                    </button>
                </div>

                <footer class="desktop-secondary">
                    <p>
                        {{ words.desktop.helper }}
                        <a href="#" @click.prevent="openHelp('access')">{{ words.desktop.requestAccess }}</a>
                    </p>
                </footer>
            </form>

            <footer class="desktop-utility">
                <nav>
                    <a href="#" @click.prevent="openHelp('privacy')">{{ words.desktop.privacy }}</a>
                    <a href="#" @click.prevent="openHelp('terms')">{{ words.desktop.terms }}</a>
                </nav>
                <p>
                    <span></span>
                    {{ words.desktop.systemStatus }}
                </p>
            </footer>
        </section>

        <section class="login-mobile-shell">
            <header class="mobile-brand">
                <h1>{{ words.brandName }}</h1>
                <p>{{ words.mobile.portalName }}</p>
            </header>

            <div class="mobile-card">
                <div class="mobile-glow mobile-glow--top"></div>
                <div class="mobile-glow mobile-glow--bottom"></div>
                <div class="mobile-card-content">
                    <header class="mobile-card-header">
                        <h2>{{ words.mobile.accessTitle }}</h2>
                        <p>{{ words.mobile.accessSubtitle }}</p>
                    </header>

                    <form class="mobile-form" @submit.prevent="handleLogin('mobile')">
                        <label class="mobile-field">
                            <span>{{ words.mobile.fields.email.label }}</span>
                            <span class="mobile-input glass-input">
                                <span class="material-symbols-outlined">person</span>
                                <input v-model="mobileForm.identity" type="text" :placeholder="words.mobile.fields.email.placeholder" autocomplete="off" inputmode="numeric">
                            </span>
                            <small v-if="errors.mobileIdentity" class="login-error">{{ errors.mobileIdentity }}</small>
                        </label>

                        <label v-if="showLoginPassword" class="mobile-field">
                            <span>Contraseña</span>
                            <span class="mobile-input glass-input">
                                <span class="material-symbols-outlined">lock</span>
                                <input ref="mobilePasswordInput" v-model="mobileForm.password" :type="showPasswordText ? 'text' : 'password'" placeholder="Ingrese su contraseña" autocomplete="current-password">
                                <button class="password-visibility" type="button" :aria-label="showPasswordText ? 'Ocultar contraseña' : 'Mostrar contraseña'" @click="showPasswordText = !showPasswordText">
                                    <span class="material-symbols-outlined">{{ showPasswordText ? 'visibility_off' : 'visibility' }}</span>
                                </button>
                            </span>
                            <small v-if="errors.mobileLoginPassword" class="login-error">{{ errors.mobileLoginPassword }}</small>
                        </label>

                        <p class="login-temp-note">Ingrese con su identidad o RTN. Si ya creó una contraseña, colóquela para acceder.</p>

                        <section class="password-create-box password-create-box--mobile">
                            <button type="button" class="password-create-toggle" @click="togglePasswordFlow">
                                <span class="material-symbols-outlined">lock_reset</span>
                                <span>Crear contraseña</span>
                            </button>

                            <div v-if="passwordFlowOpen" class="password-create-panel">
                                <p class="password-security-note">Cree una contraseña para su cliente B2B registrado por identidad/RTN.</p>

                                <label class="password-field">
                                    <span>Identidad o RTN</span>
                                    <input v-model="passwordForm.identity" type="text" inputmode="numeric" autocomplete="off" placeholder="0801**********">
                                    <small v-if="errors.createIdentity" class="login-error">{{ errors.createIdentity }}</small>
                                </label>

                                <button type="button" class="password-action" :disabled="checkingPasswordIdentity" @click="checkPasswordIdentity">
                                    {{ checkingPasswordIdentity ? 'Verificando...' : 'Verificar identidad' }}
                                </button>

                                <div v-if="passwordClient" class="password-client-card">
                                    <strong>{{ passwordClient.name }}</strong>
                                    <span>{{ passwordClient.code }} · {{ passwordClient.vatNumberMasked }}</span>
                                </div>

                                <p v-if="passwordMessage" class="password-flow-message">{{ passwordMessage }}</p>

                                <div v-if="passwordStep === 'password'" class="password-fields-stack">
                                    <label class="password-field">
                                        <span>Nueva contraseña</span>
                                        <input v-model="passwordForm.password" type="password" autocomplete="new-password" placeholder="Mínimo 8 caracteres">
                                        <small v-if="errors.newPassword" class="login-error">{{ errors.newPassword }}</small>
                                    </label>

                                    <label class="password-field">
                                        <span>Confirmar contraseña</span>
                                        <input v-model="passwordForm.password_confirmation" type="password" autocomplete="new-password" placeholder="Repita la contraseña">
                                        <small v-if="errors.passwordConfirmation" class="login-error">{{ errors.passwordConfirmation }}</small>
                                    </label>

                                    <ul class="password-rules">
                                        <li>Mínimo 8 caracteres</li>
                                        <li>Al menos una letra</li>
                                        <li>Al menos un número</li>
                                    </ul>

                                    <button type="button" class="password-action password-action--primary" :disabled="creatingPassword" @click="submitPasswordCreation">
                                        {{ creatingPassword ? 'Creando...' : 'Crear contraseña' }}
                                    </button>
                                </div>
                            </div>
                        </section>

                        <label class="mobile-remember">
                            <input id="remember-mobile" type="checkbox">
                            <span>{{ words.mobile.remember }}</span>
                        </label>

                        <button class="mobile-submit" type="submit" :disabled="loading">
                            <span>{{ loading ? 'Validando...' : words.mobile.submit }}</span>
                            <span class="material-symbols-outlined">arrow_forward</span>
                            <span class="mobile-shimmer" aria-hidden="true"></span>
                        </button>
                    </form>

                    <div class="mobile-biometrics">
                        <p>{{ words.mobile.alternateAccess }}</p>
                        <div>
                            <button type="button" class="glass-input" :aria-label="words.mobile.fingerprint" @click="openHelp('biometric')">
                                <span class="material-symbols-outlined">fingerprint</span>
                            </button>
                            <button type="button" class="glass-input" :aria-label="words.mobile.face" @click="openHelp('biometric')">
                                <span class="material-symbols-outlined">face</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="mobile-footer-actions">
                <p>{{ words.mobile.helper }}</p>
                <a href="#" @click.prevent="openHelp('access')">{{ words.mobile.requestAccess }}</a>
            </footer>

            <div class="mobile-security">
                <template v-for="(item, index) in words.mobile.security" :key="item">
                    <span>{{ item }}</span>
                    <i v-if="index < words.mobile.security.length - 1"></i>
                </template>
            </div>
        </section>

        <aside class="desktop-floating-tools" aria-hidden="true">
            <div class="liquid-glass">
                <button type="button" :aria-label="words.desktop.support" @click="openHelp('support')">
                    <span class="material-symbols-outlined">support_agent</span>
                </button>
                <span></span>
                <button type="button" :aria-label="words.desktop.language" @click="openHelp('language')">
                    <span class="material-symbols-outlined">language</span>
                </button>
            </div>
        </aside>

        <div class="mobile-stardust" aria-hidden="true"></div>
    </main>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@100;300;400;500;600;700;800;900&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');

.stitch-login {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    color: #e0e8f0;
    background: #0a0e1a;
    font-family: Inter, sans-serif;
}

.material-symbols-outlined {
    direction: ltr;
    display: inline-block;
    font-family: 'Material Symbols Outlined';
    font-feature-settings: 'liga';
    font-size: 20px;
    font-style: normal;
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    font-weight: normal;
    letter-spacing: normal;
    line-height: 1;
    text-transform: none;
    white-space: nowrap;
    word-wrap: normal;
}

.login-bg {
    position: fixed;
    inset: 0;
    z-index: 0;
}

.login-bg img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    scale: 1;
    filter: brightness(0.86) contrast(1.08) saturate(1.06);
    image-rendering: auto;
    transform: translateZ(0);
    backface-visibility: hidden;
}

.desktop-bg-gradient,
.mobile-bg-gradient-top,
.mobile-bg-gradient-radial,
.desktop-particles,
.mobile-stardust {
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.desktop-bg-gradient {
    z-index: 1;
    background: linear-gradient(to bottom, rgba(10, 14, 26, 0.4), transparent, rgba(10, 14, 26, 0.8));
}

.desktop-particles {
    z-index: 2;
    overflow: hidden;
}

.desktop-particles span {
    position: absolute;
    display: block;
    border-radius: 999px;
    filter: blur(2px);
}

.desktop-particles span:nth-child(1) {
    top: 25%;
    left: 25%;
    width: 8px;
    height: 8px;
    background: rgba(125, 211, 252, 0.4);
    opacity: 0.6;
}

.desktop-particles span:nth-child(2) {
    right: 25%;
    bottom: 33.333%;
    width: 6px;
    height: 6px;
    background: rgba(200, 160, 240, 0.3);
    opacity: 0.5;
}

.desktop-particles span:nth-child(3) {
    top: 50%;
    right: 33.333%;
    width: 4px;
    height: 4px;
    background: rgba(255, 255, 255, 0.2);
    filter: blur(1px);
    opacity: 0.4;
}

.login-desktop-shell {
    position: relative;
    z-index: 30;
    width: 100%;
    max-width: 448px;
    padding: 0 24px;
}

.desktop-brand {
    margin-bottom: 40px;
    text-align: center;
}

.desktop-brand h1 {
    margin: 0 0 8px;
    color: #e0e8f0;
    font-size: 30px;
    font-weight: 900;
    letter-spacing: 0.1em;
    line-height: 36px;
    text-shadow: 0 0 12px rgba(125, 211, 252, 0.4);
    text-transform: uppercase;
}

.desktop-brand p {
    margin: 0;
    color: #a0b4c4;
    font-size: 16px;
    font-weight: 500;
    letter-spacing: -0.025em;
    opacity: 0.8;
}

.desktop-card {
    position: relative;
    overflow: hidden;
    padding: 32px;
    border: 1px solid rgba(125, 211, 252, 0.15);
    border-radius: 12px;
    background: rgba(15, 21, 36, 0.45);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.37);
    backdrop-filter: blur(24px) saturate(180%);
}

.card-top-glow {
    position: absolute;
    top: 0;
    right: 0;
    left: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(125, 211, 252, 0.3), transparent);
}

.desktop-card-header {
    margin-bottom: 32px;
}

.desktop-card-header h2 {
    margin: 0 0 4px;
    color: #e0e8f0;
    font-size: 20px;
    font-weight: 600;
    line-height: 28px;
}

.desktop-card-header span {
    display: block;
    width: 48px;
    height: 4px;
    border-radius: 999px;
    background: #7dd3fc;
    box-shadow: 0 0 10px rgba(125, 211, 252, 0.5);
}

.desktop-form-stack {
    display: grid;
    gap: 24px;
}

.desktop-field {
    display: grid;
    gap: 8px;
}

.login-error {
    display: block;
    margin-left: 4px;
    color: #ff9b9b;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.02em;
}

.login-temp-note {
    margin: -6px 4px 0;
    color: #8fa6b8;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.5;
}

.password-create-box {
    display: grid;
    gap: 12px;
    margin-top: -8px;
}

.password-create-toggle,
.password-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid rgba(125, 211, 252, 0.3);
    border-radius: 16px;
    color: #dff7ff;
    background: rgba(8, 18, 32, 0.72);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.01em;
    cursor: pointer;
    transition: transform 180ms ease, border-color 180ms ease, background 180ms ease;
}

.password-create-toggle:hover,
.password-action:hover:not(:disabled) {
    transform: translateY(-1px);
    border-color: rgba(125, 211, 252, 0.7);
    background: rgba(14, 33, 56, 0.84);
}

.password-create-toggle .material-symbols-outlined {
    font-size: 18px;
}

.password-create-panel {
    display: grid;
    gap: 12px;
    padding: 14px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 22px;
    background: linear-gradient(135deg, rgba(7, 17, 31, 0.9), rgba(15, 34, 58, 0.74));
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08), 0 18px 40px rgba(0, 0, 0, 0.22);
}

.password-security-note,
.password-flow-message {
    margin: 0;
    color: #a9bdca;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.5;
}

.password-flow-message {
    color: #bae6fd;
}

.password-field,
.password-fields-stack {
    display: grid;
    gap: 8px;
}

.password-field > span {
    color: #a0b4c4;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.password-field input {
    width: 100%;
    min-height: 40px;
    padding: 0 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 14px;
    outline: none;
    color: #f8fafc;
    background: rgba(2, 6, 23, 0.58);
    font-size: 13px;
    font-weight: 700;
}

.password-field input:focus {
    border-color: rgba(125, 211, 252, 0.65);
    box-shadow: 0 0 0 3px rgba(125, 211, 252, 0.12);
}

.password-action {
    width: 100%;
}

.password-action--primary {
    color: #07111f;
    background: linear-gradient(135deg, #7dd3fc, #c4b5fd);
    border-color: rgba(255, 255, 255, 0.36);
}

.password-action:disabled {
    cursor: wait;
    opacity: 0.66;
}

.password-client-card {
    display: grid;
    gap: 3px;
    padding: 10px 12px;
    border: 1px solid rgba(34, 197, 94, 0.22);
    border-radius: 16px;
    background: rgba(20, 83, 45, 0.2);
}

.password-client-card strong {
    color: #dcfce7;
    font-size: 12px;
}

.password-client-card span {
    color: #bbf7d0;
    font-size: 11px;
    font-weight: 700;
}

.password-rules {
    display: grid;
    gap: 5px;
    margin: 0;
    padding-left: 18px;
    color: #cbd5e1;
    font-size: 11px;
    font-weight: 700;
}

.desktop-field > span:first-child,
.desktop-field-row > span:first-child {
    color: #a0b4c4;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.05em;
    line-height: 16px;
    margin-left: 4px;
    text-transform: uppercase;
}

.desktop-field-row {
    display: flex;
    align-items: end;
    justify-content: space-between;
    margin-left: 4px;
}

.desktop-field-row > span:first-child {
    margin-left: 0;
}

.desktop-field-row a {
    color: #7dd3fc;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: -0.04em;
    text-decoration: none;
    text-transform: uppercase;
    transition: color 180ms ease;
}

.desktop-field-row a:hover {
    color: #7dd3fc;
}

.liquid-glass {
    background: linear-gradient(135deg, rgba(125, 211, 252, 0.1), rgba(200, 160, 240, 0.05));
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.desktop-input {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    border-radius: 8px;
    transition: box-shadow 300ms ease, border-color 300ms ease;
}

.input-glow:focus-within {
    border-color: rgba(125, 211, 252, 0.5);
    box-shadow: 0 0 15px rgba(125, 211, 252, 0.2);
}

.desktop-input .material-symbols-outlined {
    margin-right: 12px;
    color: rgba(125, 211, 252, 0.6);
    font-size: 18px;
}

.desktop-input .muted-icon {
    margin-right: 0;
    color: rgba(160, 180, 196, 0.5);
    cursor: pointer;
}

.password-visibility {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    width: 28px;
    height: 28px;
    margin-left: 10px;
    border: 0;
    border-radius: 999px;
    color: rgba(160, 180, 196, 0.72);
    background: transparent;
    cursor: pointer;
    transition: color 180ms ease, background 180ms ease;
}

.password-visibility:hover {
    color: #7dd3fc;
    background: rgba(125, 211, 252, 0.12);
}

.desktop-input .password-visibility .material-symbols-outlined,
.mobile-input .password-visibility .material-symbols-outlined {
    position: static;
    margin: 0;
    color: currentColor;
    font-size: 18px;
    transform: none;
}

.desktop-input input,
.mobile-input input {
    width: 100%;
    min-width: 0;
    padding: 0;
    border: 0;
    outline: 0;
    color: #e0e8f0;
    background: transparent;
    font: inherit;
}

.desktop-input input {
    font-size: 14px;
    font-weight: 500;
}

.desktop-input input::placeholder {
    color: rgba(160, 180, 196, 0.4);
}

.desktop-remember {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: 4px;
    color: #a0b4c4;
    cursor: pointer;
    font-size: 12px;
    user-select: none;
}

.checkbox-wrap {
    position: relative;
    width: 16px;
    height: 16px;
}

.checkbox-wrap input {
    width: 16px;
    height: 16px;
    margin: 0;
    appearance: none;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.05);
    transition: background 180ms ease;
}

.checkbox-wrap input:checked {
    background: #7dd3fc;
}

.checkbox-wrap .material-symbols-outlined {
    position: absolute;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    color: #001f2e;
    font-size: 12px;
    pointer-events: none;
}

.checkbox-wrap input:checked + .material-symbols-outlined {
    display: flex;
}

.desktop-submit {
    width: 100%;
    position: relative;
    overflow: hidden;
    padding: 1px;
    border: 0;
    border-radius: 8px;
    background: linear-gradient(90deg, rgba(125, 211, 252, 0.5), rgba(200, 160, 240, 0.5), rgba(125, 211, 252, 0.5));
    cursor: pointer;
}

.submit-inner {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px 24px;
    border-radius: 7px;
    background: rgba(14, 77, 110, 0.8);
    color: #7dd3fc;
    backdrop-filter: blur(12px);
    transition: background 180ms ease, transform 180ms ease;
}

.desktop-submit:hover .submit-inner {
    background: #0e4d6e;
}

.desktop-submit:active .submit-inner {
    transform: scale(0.98);
}

.submit-inner > span:first-child {
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-shadow: 0 0 8px rgba(125, 211, 252, 0.4);
    text-transform: uppercase;
}

.submit-inner .material-symbols-outlined {
    margin-left: 8px;
    color: #7dd3fc;
    transition: transform 180ms ease;
}

.desktop-submit:hover .material-symbols-outlined {
    transform: translateX(4px);
}

.desktop-secondary {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    text-align: center;
}

.desktop-secondary p {
    margin: 0;
    color: #a0b4c4;
    font-size: 12px;
}

.desktop-secondary a {
    margin-left: 4px;
    color: #7dd3fc;
    font-weight: 700;
    text-decoration: none;
}

.desktop-utility {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 48px;
    padding: 0 16px;
}

.desktop-utility nav {
    display: flex;
    gap: 24px;
}

.desktop-utility a,
.desktop-utility p {
    color: rgba(160, 180, 196, 0.6);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-decoration: none;
    text-transform: uppercase;
}

.desktop-utility p {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

.desktop-utility p span {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
}

.desktop-floating-tools {
    position: fixed;
    right: 40px;
    bottom: 40px;
    z-index: 40;
}

.desktop-floating-tools > div {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    padding: 12px;
    border-radius: 999px;
}

.desktop-floating-tools button {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 0;
    color: rgba(125, 211, 252, 0.7);
    background: transparent;
}

.desktop-floating-tools div > span {
    width: 24px;
    height: 1px;
    background: rgba(255, 255, 255, 0.1);
}

.login-mobile-shell,
.mobile-stardust,
.mobile-bg-gradient-top,
.mobile-bg-gradient-radial {
    display: none;
}

@media (max-width: 767px) {
    .stitch-login {
        align-items: stretch;
        min-height: 100vh;
        overflow: hidden;
    }

    .login-bg img {
        scale: 1;
        filter: brightness(0.5);
    }

    .desktop-bg-gradient,
    .desktop-particles,
    .login-desktop-shell,
    .desktop-floating-tools {
        display: none;
    }

    .mobile-bg-gradient-top,
    .mobile-bg-gradient-radial {
        display: block;
    }

    .mobile-bg-gradient-top {
        background: linear-gradient(to top, rgba(10, 14, 26, 0.8), transparent, transparent);
        opacity: 0.8;
    }

    .mobile-bg-gradient-radial {
        background: radial-gradient(circle at center, transparent 0%, rgba(10, 14, 26, 0.4) 42%, #0a0e1a 100%);
        opacity: 0.9;
    }

    .login-mobile-shell {
        position: relative;
        z-index: 10;
        display: flex;
        min-height: 100vh;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }

    .mobile-brand {
        margin-bottom: 48px;
        text-align: center;
    }

    .mobile-brand h1 {
        margin: 0 0 8px;
        color: #7dd3fc;
        font-size: 36px;
        font-weight: 900;
        line-height: 40px;
        letter-spacing: 0.1em;
        text-shadow: 0 0 15px rgba(125, 211, 252, 0.4);
    }

    .mobile-brand p {
        margin: 0;
        color: rgba(160, 180, 196, 0.8);
        font-size: 14px;
        font-weight: 500;
        letter-spacing: -0.025em;
        line-height: 20px;
        text-transform: uppercase;
    }

    .mobile-card {
        position: relative;
        width: 100%;
        max-width: 384px;
        overflow: hidden;
        padding: 32px;
        border: 1px solid rgba(125, 211, 252, 0.1);
        border-radius: 12px;
        background: rgba(15, 21, 36, 0.6);
        box-shadow: 0 25px 50px rgba(3, 7, 17, 0.8);
        backdrop-filter: blur(16px);
    }

    .mobile-glow {
        position: absolute;
        width: 128px;
        height: 128px;
        border-radius: 50%;
        filter: blur(60px);
    }

    .mobile-glow--top {
        top: -40px;
        right: -40px;
        background: rgba(125, 211, 252, 0.1);
    }

    .mobile-glow--bottom {
        bottom: -40px;
        left: -40px;
        background: rgba(200, 160, 240, 0.1);
    }

    .mobile-card-content {
        position: relative;
        z-index: 1;
    }

    .mobile-card-header {
        margin-bottom: 32px;
    }

    .mobile-card-header h2 {
        margin: 0;
        color: #e0e8f0;
        font-size: 24px;
        font-weight: 700;
        line-height: 32px;
    }

    .mobile-card-header p {
        margin: 4px 0 0;
        color: #a0b4c4;
        font-size: 14px;
        line-height: 20px;
    }

    .mobile-form {
        display: grid;
        gap: 24px;
    }

    .mobile-field {
        display: grid;
        gap: 8px;
    }

    .mobile-field > span:first-child,
    .mobile-field-row > span:first-child {
        margin-left: 4px;
        color: #a0b4c4;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }

    .mobile-field-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 4px;
    }

    .mobile-field-row > span:first-child {
        margin-left: 0;
    }

    .mobile-field-row a {
        color: rgba(125, 211, 252, 0.7);
        font-size: 10px;
        font-weight: 700;
        text-decoration: none;
        text-transform: uppercase;
    }

    .glass-input {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(125, 211, 252, 0.15);
        transition: all 0.3s ease;
    }

    .glass-input:focus-within,
    .glass-input:focus {
        border-color: #7dd3fc;
        outline: none;
        background: rgba(125, 211, 252, 0.05);
        box-shadow: 0 0 15px rgba(125, 211, 252, 0.1);
    }

    .mobile-input {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 16px 16px 48px;
        border-radius: 8px;
    }

    .mobile-input .material-symbols-outlined {
        position: absolute;
        left: 16px;
        top: 50%;
        color: rgba(160, 180, 196, 0.6);
        font-size: 18px;
        transform: translateY(-50%);
    }

    .mobile-input input {
        color: #e0e8f0;
        font-size: 14px;
        line-height: 20px;
    }

    .mobile-input input::placeholder {
        color: rgba(160, 180, 196, 0.3);
    }

    .mobile-remember {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 0 4px;
        color: #a0b4c4;
        font-size: 12px;
        font-weight: 500;
        user-select: none;
    }

    .mobile-remember input {
        width: 16px;
        height: 16px;
        margin: 0;
        accent-color: #7dd3fc;
    }

    .mobile-submit {
        position: relative;
        width: 100%;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 16px;
        border: 1px solid rgba(125, 211, 252, 0.3);
        border-radius: 8px;
        color: #7dd3fc;
        background: rgba(125, 211, 252, 0.1);
        font-weight: 700;
        transition: transform 200ms ease, background 200ms ease;
    }

    .mobile-submit:hover {
        background: rgba(125, 211, 252, 0.2);
    }

    .mobile-submit:active {
        transform: scale(0.95);
    }

    .mobile-submit > span:not(.mobile-shimmer) {
        position: relative;
        z-index: 1;
    }

    .mobile-shimmer {
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
        pointer-events: none;
        transform: translateX(-100%);
    }

    .mobile-submit:hover .mobile-shimmer {
        animation: shimmer 2s infinite;
    }

    .mobile-biometrics {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 16px;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }

    .mobile-biometrics p {
        margin: 0;
        color: rgba(160, 180, 196, 0.4);
        font-size: 10px;
        font-weight: 900;
        letter-spacing: 0.2em;
        text-transform: uppercase;
    }

    .mobile-biometrics div {
        display: flex;
        gap: 16px;
    }

    .mobile-biometrics button {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        color: #a0b4c4;
        background: rgba(255, 255, 255, 0.03);
    }

    .mobile-biometrics button:hover {
        color: #7dd3fc;
    }

    .mobile-biometrics .material-symbols-outlined {
        font-size: 24px;
    }

    .mobile-footer-actions {
        margin-top: 32px;
        text-align: center;
    }

    .mobile-footer-actions p {
        margin: 0;
        color: rgba(160, 180, 196, 0.6);
        font-size: 14px;
    }

    .mobile-footer-actions a {
        display: inline-block;
        margin-top: 4px;
        color: #7dd3fc;
        font-weight: 700;
        text-decoration: none;
    }

    .mobile-security {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 48px;
        color: rgba(160, 180, 196, 0.3);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.3em;
        text-transform: uppercase;
    }

    .mobile-security i {
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: #7dd3fc;
    }

    .mobile-stardust {
        position: fixed;
        z-index: 1;
        display: block;
        background-image: radial-gradient(rgba(255, 255, 255, 0.58) 0.6px, transparent 0.6px);
        background-size: 11px 11px;
        opacity: 0.1;
    }

    @keyframes shimmer {
        100% { transform: translateX(100%); }
    }
}

@media (max-width: 390px) {
    .login-mobile-shell {
        padding: 18px;
    }

    .mobile-brand {
        margin-bottom: 34px;
    }

    .mobile-brand h1 {
        font-size: 30px;
        line-height: 34px;
    }

    .mobile-card {
        padding: 24px;
    }
}

.login-bg img {
    filter: none !important;
    image-rendering: auto;
    object-position: center;
    scale: 1 !important;
    transform: none !important;
}

.desktop-bg-gradient {
    background: linear-gradient(180deg, rgba(10, 14, 26, 0.16), rgba(10, 14, 26, 0.28) 48%, rgba(10, 14, 26, 0.62)) !important;
}

.mobile-bg-gradient-top {
    background: linear-gradient(180deg, rgba(10, 14, 26, 0.3), transparent 52%) !important;
}
</style>
