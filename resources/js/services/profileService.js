import { profileActivity, profileFields } from '../data/mockProfile.js';
import { portalUser } from '../portalNavigation.js';
import { createDataAdapter } from './dataAdapter.js';

const emptyMessage = 'No hay cliente B2B configurado para esta sesión.';
const fallbackUser = { name: portalUser.name, avatar: portalUser.avatar, email: '', phone: '' };
const fallbackCompany = { name: 'Cliente B2B', code: 'Sin configurar', rtn: '', address: '', city: '', country: 'Honduras' };
const fallbackCommercial = { partnerLevel: 'Socio B2B', creditCondition: 'Consultar', creditLimit: 'L. 0.00', creditLimitValue: 0, availableCredit: 'L. 0.00', availableCreditValue: 0 };

const fieldsFromProfile = (user, company, commercial) => [
    { icon: 'badge', label: 'Código Cliente', value: company.code || 'Sin configurar' },
    { icon: 'business', label: 'Empresa', value: company.name || 'Cliente B2B' },
    { icon: 'mail', label: 'Correo', value: user.email || 'No registrado' },
    { icon: 'call', label: 'Teléfono', value: user.phone || 'No registrado' },
    { icon: 'location_on', label: 'Dirección', value: company.address || 'No registrada' },
    { icon: 'verified_user', label: 'Condición', value: commercial.creditCondition || 'Consultar' },
];

const normalizeProfile = (payload) => {
    const clientConfigured = payload.meta?.client_configured ?? payload.client_configured ?? Boolean(payload.user || payload.company || payload.fields || payload.profileFields);
    const user = payload.user ? { ...fallbackUser, ...payload.user, avatar: payload.user.avatar || fallbackUser.avatar } : fallbackUser;
    const company = payload.company ? { ...fallbackCompany, ...payload.company } : fallbackCompany;
    const commercial = payload.commercial ? { ...fallbackCommercial, ...payload.commercial } : fallbackCommercial;

    return {
        user,
        company,
        commercial,
        clientConfigured,
        emptyMessage,
        profileFields: payload.profileFields ?? payload.fields ?? fieldsFromProfile(user, company, commercial),
        profileActivity: payload.profileActivity ?? payload.activity ?? (clientConfigured ? profileActivity : []),
    };
};

const profileResource = createDataAdapter({
    mock: () => ({ profileFields, profileActivity }),
    endpoint: '/api/portal/profile',
    extract: (response) => ({ ...(response?.data ?? {}), meta: response?.meta ?? {} }),
    normalize: normalizeProfile,
});

export const getProfileUser = () => profileResource.list().user;
export const getProfileCompany = () => profileResource.list().company;
export const getProfileCommercial = () => profileResource.list().commercial;
export const getProfileFields = () => profileResource.list().profileFields;
export const getProfileActivity = () => profileResource.list().profileActivity;
export const getProfileClientConfigured = () => profileResource.list().clientConfigured;
export const getProfileEmptyMessage = () => profileResource.list().emptyMessage;
export const fetchProfile = (params = {}) => profileResource.fetch(params);
export const profileState = profileResource.state;
