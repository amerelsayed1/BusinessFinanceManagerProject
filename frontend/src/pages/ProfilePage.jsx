import React from 'react';

export default function ProfilePage() {
  return (
    <div className="bg-white rounded-lg shadow-lg p-6 max-w-xl mx-auto">
      <h2 className="text-2xl font-bold text-gray-800 mb-4">Profile</h2>
      <p className="text-sm text-gray-600 mb-4">
        This is a simple local profile for your finance workspace. In a real
        app, this would be connected to authentication and a backend.
      </p>

      <div className="space-y-4">
        <div>
          <label className="block text-sm text-gray-700 mb-1">
            Business name
          </label>
          <input
            type="text"
            className="border rounded px-3 py-2 w-full"
            placeholder="My Store"
            defaultValue="My Store"
          />
        </div>

        <div>
          <label className="block text-sm text-gray-700 mb-1">
            Owner name
          </label>
          <input
            type="text"
            className="border rounded px-3 py-2 w-full"
            placeholder="Your name"
          />
        </div>

        <div>
          <label className="block text-sm text-gray-700 mb-1">
            Business type
          </label>
          <select className="border rounded px-3 py-2 w-full" defaultValue="online_store">
            <option value="online_store">Online Store</option>
            <option value="restaurant">Restaurant / Café</option>
            <option value="services">Services</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div className="pt-2 border-t mt-4">
          <p className="text-sm text-gray-500">
            Currency is fixed to <span className="font-semibold">EGP</span> in this version.
          </p>
        </div>
      </div>
    </div>
  );
}
