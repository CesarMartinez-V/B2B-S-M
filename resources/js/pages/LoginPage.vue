<script setup>
import { reactive } from 'vue';
import { useAuth } from '../composables/useAuth.js';
import { useModal } from '../composables/useModal.js';
import { useToast } from '../composables/useToast.js';
import { PalabrasWeb } from '../PalabrasWeb.js';

const words = PalabrasWeb.auth;
const backgroundImage = new URL('../../../stitch_inversiones_s_m_future_b2b_portal/stitch_inversiones_s_m_future_b2b_portal/cinematic_wide_shot_of_a_futuristic_luxury_electric_sports_car_interior_and/screen.png', import.meta.url).href;
const { login } = useAuth();
const { openModal } = useModal();
const { error, success } = useToast();

const form = reactive({
    username: '',
    password: '',
});

const mobileForm = reactive({
    username: '',
    password: '',
});

const errors = reactive({
    username: '',
    password: '',
    mobileUsername: '',
    mobilePassword: '',
});

const validate = (values, mode = 'desktop') => {
    const usernameKey = mode === 'mobile' ? 'mobileUsername' : 'username';
    const passwordKey = mode === 'mobile' ? 'mobilePassword' : 'password';

    errors[usernameKey] = values.username.trim() ? '' : 'Este campo es requerido.';
    errors[passwordKey] = values.password.trim() ? '' : 'La contraseña es requerida.';

    return !errors[usernameKey] && !errors[passwordKey];
};

const handleLogin = (mode = 'desktop') => {
    const values = mode === 'mobile' ? mobileForm : form;

    if (!validate(values, mode)) {
        error('Completa usuario y contraseña para ingresar.');
        return;
    }

    login({ username: values.username.trim(), password: values.password });
    success('Sesión temporal iniciada.');
    window.location.href = '/panel';
};

const openHelp = (type) => {
    const messages = {
        forgot: 'Ingresa tu correo corporativo y un asesor B2B validará el restablecimiento de acceso.',
        access: 'Tu solicitud de acceso quedará registrada para revisión comercial.',
        support: 'Soporte B2B registrará tu consulta de acceso y te contactará por los canales asociados a tu cuenta.',
        language: 'El portal mantiene idioma español para esta versión funcional.',
    };

    openModal({
        title: type === 'forgot' ? 'Recuperar contraseña' : type === 'access' ? 'Solicitar acceso' : type === 'language' ? 'Idioma del portal' : 'Soporte de acceso',
        message: messages[type] || messages.support,
        icon: type === 'language' ? 'language' : 'support_agent',
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
                            <input v-model="form.username" type="text" :placeholder="words.desktop.fields.username.placeholder" autocomplete="username">
                        </span>
                        <small v-if="errors.username" class="login-error">{{ errors.username }}</small>
                    </label>

                    <label class="desktop-field">
                        <span class="desktop-field-row">
                            <span>{{ words.desktop.fields.password.label }}</span>
                            <a href="#" @click.prevent="openHelp('forgot')">{{ words.desktop.forgotPassword }}</a>
                        </span>
                        <span class="desktop-input liquid-glass input-glow">
                            <span class="material-symbols-outlined">lock</span>
                            <input v-model="form.password" type="password" :placeholder="words.desktop.fields.password.placeholder" autocomplete="current-password">
                            <span class="material-symbols-outlined muted-icon">visibility</span>
                        </span>
                        <small v-if="errors.password" class="login-error">{{ errors.password }}</small>
                    </label>

                    <label class="desktop-remember">
                        <span class="checkbox-wrap">
                            <input id="remember-desktop" type="checkbox">
                            <span class="material-symbols-outlined">check</span>
                        </span>
                        <span>{{ words.desktop.remember }}</span>
                    </label>

                    <button class="desktop-submit" type="submit">
                        <span class="submit-inner">
                            <span>{{ words.desktop.submit }}</span>
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
                    <a href="#" @click.prevent="openHelp('support')">{{ words.desktop.privacy }}</a>
                    <a href="#" @click.prevent="openHelp('support')">{{ words.desktop.terms }}</a>
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
                                <input v-model="mobileForm.username" type="email" :placeholder="words.mobile.fields.email.placeholder" autocomplete="email">
                            </span>
                            <small v-if="errors.mobileUsername" class="login-error">{{ errors.mobileUsername }}</small>
                        </label>

                        <label class="mobile-field">
                            <span class="mobile-field-row">
                                <span>{{ words.mobile.fields.password.label }}</span>
                                <a href="#" @click.prevent="openHelp('forgot')">{{ words.mobile.forgotPassword }}</a>
                            </span>
                            <span class="mobile-input glass-input">
                                <span class="material-symbols-outlined">lock</span>
                                <input v-model="mobileForm.password" type="password" :placeholder="words.mobile.fields.password.placeholder" autocomplete="current-password">
                            </span>
                            <small v-if="errors.mobilePassword" class="login-error">{{ errors.mobilePassword }}</small>
                        </label>

                        <label class="mobile-remember">
                            <input id="remember-mobile" type="checkbox">
                            <span>{{ words.mobile.remember }}</span>
                        </label>

                        <button class="mobile-submit" type="submit">
                            <span>{{ words.mobile.submit }}</span>
                            <span class="material-symbols-outlined">arrow_forward</span>
                            <span class="mobile-shimmer" aria-hidden="true"></span>
                        </button>
                    </form>

                    <div class="mobile-biometrics">
                        <p>{{ words.mobile.alternateAccess }}</p>
                        <div>
                            <button type="button" class="glass-input" :aria-label="words.mobile.fingerprint">
                                <span class="material-symbols-outlined">fingerprint</span>
                            </button>
                            <button type="button" class="glass-input" :aria-label="words.mobile.face">
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
