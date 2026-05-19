import { profileActivity, profileFields } from '../data/mockProfile.js';
import { createDataAdapter } from './dataAdapter.js';

const profileResource = createDataAdapter({
    mock: () => ({ profileFields, profileActivity }),
    endpoint: '/api/portal/profile',
    extract: (response) => response?.data ?? {},
    normalize: (payload) => ({
        profileFields: payload.profileFields ?? payload.fields ?? profileFields,
        profileActivity: payload.profileActivity ?? payload.activity ?? profileActivity,
    }),
});

export const getProfileFields = () => profileResource.list().profileFields;
export const getProfileActivity = () => profileResource.list().profileActivity;
export const fetchProfile = (params = {}) => profileResource.fetch(params);
export const profileState = profileResource.state;
