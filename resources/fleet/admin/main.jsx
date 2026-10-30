import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router';
import { AuthProvider, GuestOnly, RequireAuth, useAuth } from './auth';
import Layout from './Layout';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import VehicleList from './pages/vehicles/VehicleList';
import VehicleForm from './pages/vehicles/VehicleForm';
import VehicleDetail from './pages/vehicles/VehicleDetail';
import VehicleCatalog from './pages/vehicles/VehicleCatalog';
import BeneficiaryList from './pages/beneficiaries/BeneficiaryList';
import BeneficiaryForm from './pages/beneficiaries/BeneficiaryForm';
import BeneficiaryDetail from './pages/beneficiaries/BeneficiaryDetail';
import Assignments from './pages/Assignments';
import HandoverForm from './pages/handovers/HandoverForm';
import HandoverDetail from './pages/handovers/HandoverDetail';
import RecallList from './pages/recalls/RecallList';
import RecallForm from './pages/recalls/RecallForm';
import RecallDetail from './pages/recalls/RecallDetail';
import Import from './pages/Import';
import EvaluationList from './pages/evaluations/EvaluationList';
import EvaluationDetail from './pages/evaluations/EvaluationDetail';
import Admins from './pages/Admins';
import Compliance from './pages/Compliance';
import MessageLog from './pages/messages/MessageLog';
import Templates from './pages/messages/Templates';
import SendMessage from './pages/messages/SendMessage';

/**
 * Hides pages from roles that can't use them (the API refuses them anyway).
 */
function Only({ roles, children }) {
    const { user } = useAuth();
    return roles.includes(user.role) ? children : <Navigate to="/" replace />;
}

const MANAGERS = ['super_admin', 'fleet_officer'];

createRoot(document.getElementById('root')).render(
    <StrictMode>
        <BrowserRouter basename="/admin">
            <AuthProvider>
                <Routes>
                    <Route path="/login" element={<GuestOnly><Login /></GuestOnly>} />
                    <Route element={<RequireAuth><Layout /></RequireAuth>}>
                        <Route index element={<Dashboard />} />

                        <Route path="vehicles" element={<VehicleList />} />
                        <Route path="vehicles/new" element={<Only roles={MANAGERS}><VehicleForm /></Only>} />
                        <Route path="vehicles/:id" element={<VehicleDetail />} />
                        <Route path="vehicles/:id/edit" element={<Only roles={MANAGERS}><VehicleForm /></Only>} />
                        <Route path="vehicle-catalog" element={<Only roles={['super_admin']}><VehicleCatalog /></Only>} />

                        <Route path="beneficiaries" element={<BeneficiaryList />} />
                        <Route path="beneficiaries/new" element={<Only roles={MANAGERS}><BeneficiaryForm /></Only>} />
                        <Route path="beneficiaries/:id" element={<BeneficiaryDetail />} />
                        <Route path="beneficiaries/:id/edit" element={<Only roles={MANAGERS}><BeneficiaryForm /></Only>} />

                        <Route path="assignments" element={<Assignments />} />
                        <Route path="assignments/:id/return" element={<Only roles={MANAGERS}><HandoverForm mode="return" /></Only>} />
                        <Route path="handovers/new" element={<Only roles={MANAGERS}><HandoverForm mode="handover" /></Only>} />
                        <Route path="handovers/:id" element={<HandoverDetail />} />

                        <Route path="evaluations" element={<EvaluationList />} />
                        <Route path="compliance" element={<Compliance />} />
                        <Route path="evaluations/:id" element={<EvaluationDetail />} />

                        <Route path="recalls" element={<RecallList />} />
                        <Route path="recalls/new" element={<Only roles={MANAGERS}><RecallForm /></Only>} />
                        <Route path="recalls/:id" element={<RecallDetail />} />

                        <Route path="messages" element={<MessageLog />} />
                        <Route path="messages/templates" element={<Templates />} />
                        <Route path="messages/send" element={<Only roles={MANAGERS}><SendMessage /></Only>} />

                        <Route path="import" element={<Only roles={MANAGERS}><Import /></Only>} />
                        <Route path="admins" element={<Only roles={['super_admin']}><Admins /></Only>} />
                    </Route>
                    <Route path="*" element={<Navigate to="/" replace />} />
                </Routes>
            </AuthProvider>
        </BrowserRouter>
    </StrictMode>,
);
