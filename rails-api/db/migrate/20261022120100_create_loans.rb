class CreateLoans < ActiveRecord::Migration[8.1]
  def change
    create_table :loans do |t|
      t.references :book, null: false, foreign_key: true, index: false
      t.string :borrower_name, null: false
      t.date :due_on, null: false
      t.datetime :returned_at
      t.timestamps
    end
    # Covers "active loans for a book" (availability) and the overdue listing.
    add_index :loans, %i[book_id returned_at]
    add_index :loans, %i[returned_at due_on]
  end
end
