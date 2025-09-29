# Kargo National Shipping for WooCommerce

This documentation provides a simple guide to installing, setting up, and using the Kargo National Shipping plugin for WooCommerce.

---

## 1. Overview

The **Kargo National Shipping** plugin integrates Kargo National (a local South African logistics and fulfillment company) as a live-rate shipping option within your WooCommerce store. This allows you to provide customers with accurate shipping rates based on item weight, dimensions, and destination.

---

## 2. Getting Started: Obtain API Credentials (Crucial First Step)

Before you can configure the plugin, you must obtain your necessary API credentials from Kargo National.

**Action:** Email Kargo National on **`<email address>`** to request your API connection details.

You will need the following details during configuration:
* Kargo National Account Number
* API Username
* API Key

---

## 3. Installation

### 3.1. How to Download

Download the latest stable release of the plugin as a `.zip` file:

**[Download Latest Release (v0.0.3)](https://github.com/hereandnowdigital/kargo-shipping/releases/tag/0.0.3)**

### 3.2. How to Install

1.  Log into your WordPress Administrator Dashboard.
2.  Navigate to **Plugins** > **Add New**.
3.  Click the **Upload Plugin** button at the top of the screen.
4.  Click **Choose File** and select the `.zip` file you downloaded from the link above.
5.  Click **Install Now**.
6.  Once the installation is complete, click **Activate Plugin**.

---

## 4. Setup and Configuration

Once installed, the Kargo National shipping method must be enabled and configured within a WooCommerce Shipping Zone.

### 4.1. How to Enable the Shipping Method

1.  Navigate to **WooCommerce** > **Settings** > **Shipping**.
2.  Select or create a **Shipping Zone** that covers **South Africa** (The Kargo National service is only available for destinations within South Africa).
3.  Click **Add Shipping Method** within that zone.
4.  Select **Kargo National Shipping** from the dropdown menu and click **Add Shipping Method**.

### 4.2. How to Configure the Shipping Method

1.  Click **Edit** on the newly added **Kargo National Shipping** method.
2.  Fill in the required fields using the credentials you obtained in Step 2:
    * **Kargo National Account Number:** Enter your unique Kargo National Account Number.
    * **API Username:** Enter the API Username provided by Kargo National.
    * **API Key:** Enter the API Key provided by Kargo National.
    * **Origin Post Code:** Enter the postal code from which your shipments will be collected. *This defaults to your Shop Base Post Code.*

3.  Click **Save changes**.

---

## 5. Exceptions and Requirements

Kargo National Shipping will only be available to the customer if **all** the following requirements are met for the entire order:

| Requirement | Details |
| :--- | :--- |
| **Destination** | Only available for destinations **inside South Africa**. |
| **Product Data** | **All items** in the customer's cart must have **weight or dimensions** (Width, Height, and Depth) accurately entered in the WooCommerce product data settings. |
| **Maximum Weight** | Individual item weight must be **less than 150kg**. |
| **Maximum Dimensions**| Individual item dimensions (Width, Height, or Depth) must be **less than 120cm**. |

If any item in the cart violates these rules, the **Kargo National Shipping** method will not be displayed, and the customer will not be able to select it.

---

## 6. Support

For any queries or assistance in using this plugin, please contact:

**`<contact details>`**