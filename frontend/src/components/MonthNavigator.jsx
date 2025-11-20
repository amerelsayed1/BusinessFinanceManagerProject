import React from 'react';

export default function MonthNavigator({ label, onPrev, onNext }) {
  return (
    <div className="flex items-center gap-2">
      <button
        type="button"
        onClick={onPrev}
        className="px-3 py-1 rounded border border-gray-300 text-sm hover:bg-gray-100"
      >
        ◀
      </button>
      <span className="text-sm font-semibold text-gray-700 whitespace-nowrap">
        {label}
      </span>
      <button
        type="button"
        onClick={onNext}
        className="px-3 py-1 rounded border border-gray-300 text-sm hover:bg-gray-100"
      >
        ▶
      </button>
    </div>
  );
}
